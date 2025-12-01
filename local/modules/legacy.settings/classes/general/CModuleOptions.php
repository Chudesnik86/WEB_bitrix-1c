<?php
use \Bitrix\Main\Config\Option;

class CModuleOptions
{
    private $module_id = '';
    private $arTabs = [];
    private $arGroups = [];
    private $arOptions = [];
    public $request = [];
    public $defaultOptions = [];

    function __construct($module_id, $arTabs, $arGroups, $arOptions)
    {
        $this->module_id = $module_id;
        $this->arTabs = $arTabs;
        $this->arGroups = $arGroups;
        $this->arOptions = $arOptions;
        $this->request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();;
        $this->defaultOptions = \Bitrix\Main\Config\Option::getDefaults($this->module_id);

        //сохраняем отправленные данные
        if($this->request->isPost() && $this->request['Update'] && check_bitrix_sessid()){
            $this->saveOptions();
        }

        //заполняем дефолтными значениями (после сохранения, т.к. отправленные данные затирают дефолтные, если пустая строка)
        $this->initializeValues();
    }

    private function initializeValues(){
        foreach ($this->arOptions as $arOption)
        {
            $name = $arOption['NAME'];
            $value = Option::get('legacy.settings', $name);

            $default = $this->defaultOptions[$name];
            if (!$value && $default){
                Option::set('legacy.settings', $name, $default);
            }
        }
    }

    private function saveOptions()
    {
        foreach ($this->arOptions as $arOption)
        {
            if (!is_array($arOption))
                continue;

            if ($arOption['note'])
                continue;

            __AdmSettingsSaveOption($this->module_id, [$arOption['NAME']]);
        }
    }

    public function showOptions()
    {

        global $APPLICATION;

        $tabControl = new CAdminTabControl('tabControl', $this->arTabs);
        $tabControl->Begin();

        echo '<form method="post" action="' . $APPLICATION->GetCurPage() . '?mid=' . htmlspecialcharsbx($this->request['mid']) . '&amp;lang=' . $this->request['lang'] .'" name="legacy_settings">';

        foreach ($this->arTabs as $arTab){
            $tabControl->BeginNextTab();

            $aTabGroups = array_filter($this->arGroups, function($arGroup) use ($arTab) {
                return $arGroup['TAB'] == $arTab['DIV'];
            });
            foreach ($aTabGroups as $key => $aTabGroup){
                echo '<tr class="heading"><td colspan="2">'.$aTabGroup['TITLE'].'</td></tr>';

                $arTabOptions = array_filter($this->arOptions, function($arOption) use ($key) {
                    return $arOption['GROUP'] == $key;
                });

                array_multisort(array_column($arTabOptions, 'SORT'), SORT_ASC, $arTabOptions);
                foreach ($arTabOptions as $arTabOption){
                    self::echoOptionHTML($arTabOption);
                }
            }
        }

        $tabControl->BeginNextTab();
        $tabControl->Buttons();
        echo '<input type="submit" name="Update" value="' . GetMessage('MAIN_SAVE').'">
            <input type="reset" name="reset" value="' . GetMessage('MAIN_RESET') . '">' . bitrix_sessid_post();
        echo '</form>';
        $tabControl->End();

    }

    private static function echoOptionHTML($arTabOption){
        $name = $arTabOption['NAME'];
        $title = $arTabOption['TITLE'];
        $value = Option::get('legacy.settings', $name);

        switch($arTabOption['TYPE'])
            {
                case 'CHECKBOX':
                    $checked = $value ? "checked" : "";
                    echo
'<tr>
    <td class="adm-detail-valign-top adm-detail-content-cell-l" width="50%" style="    vertical-align: middle;">
        '.$title.':
    </td>
    <td width="50%" class="adm-detail-content-cell-r">
        <div id="'.$name.'" style="display:flex;">
            <input type="text" style="display: none" name="'.$name.'" value="'.$value.'">
            <input type="checkbox" name="checkbox" value="Y"'. $checked .'>
        </div>
    </td>
    <script>
        const value_'.$name.' = document.querySelector("div#'.$name.' input[name=\''.$name.'\']");
        const checkbox_'.$name.' = document.querySelector("div#'.$name.' input[name=\'checkbox\']");
        checkbox_'.$name.'.addEventListener("change", (event)=> {
            value_'.$name.'.value = event.target.checked ? event.target.value : "";
        });
    </script>
</tr>';
                   break;
                case 'SELECT':
                    $options = array_reduce($arTabOption['OPTIONS'],
                        function ($res, $option) use ($value) {
                            $checked = $option['VALUE'] === $value ? "selected" : "";
                            $res .='<option value="'.$option['VALUE'].'"'.$checked.'>'.$option['TITLE'].'</option>';
                            return $res;
                        }, ''
                    );
                    echo
'<tr>
    <td class="adm-detail-valign-top adm-detail-content-cell-l" width="50%" style="    vertical-align: middle;">
        '.$title.':
    </td>
    <td width="50%" class="adm-detail-content-cell-r">
        <div id="'.$name.'" style="display:flex;">
            <select name="'.$name.'">
                '.$options.'
            </select>
        </div>
    </td>
</tr>';

                    break;
                case 'MSELECT':
                    $values = explode("<>", $value);
                    $options = array_reduce($arTabOption['OPTIONS'],
                        function ($res, $option) use ($values) {
                            $checked = in_array($option['VALUE'], $values) ? "selected" : "";
                            $res .='<option value="'.$option['VALUE'].'"'.$checked.'>'.$option['TITLE'].'</option>';
                            return $res;
                        }, ''
                    );
                    echo
'<tr>
    <td class="adm-detail-valign-top adm-detail-content-cell-l" width="50%" style="    vertical-align: middle;">
        '.$title.':
    </td>
    <td width="50%" class="adm-detail-content-cell-r">
        <div id="'.$name.'" style="display:flex;">
            <input type="text" style="display: none" name="'.$name.'" value="'.$value.'">
            <select name="select" multiple>
                '.$options.'
            </select>
        </div>
    </td>
    <script>
        const value_'.$name.' = document.querySelector("div#'.$name.' input[name=\''.$name.'\']");
        const select'.$name.' = document.querySelector("div#'.$name.' select[name=\'select\']")
        select'.$name.'.addEventListener("change", (event)=> {
            const newVal = Array.from(event.target.selectedOptions)
                .map(option => option.value)
                .join("<>");
            value_'.$name.'.value = newVal;
        });
    </script>
</tr>';

                    break;
                case 'COLOR':
                    echo
'<tr>
    <td class="adm-detail-valign-top adm-detail-content-cell-l" width="50%" style="    vertical-align: middle;">
        '.$title.':
    </td>
    <td width="50%" class="adm-detail-content-cell-r">
        <div id="'.$name.'" style="display:flex; gap: 5px;">
            <div style="position: relative;">
                <input type="text" name="'.$name.'" pattern="#[\dA-Za-z]{6}" value="'.$value.'">
                <span class="tooltip">
                    Введите значение в формате "#FFFFFF"
                </span>
            </div>
            <input type="color" name="colorpicker" value="'.$value.'">
        </div>
    </td>
    <script>
        const value_'.$name.' = document.querySelector("div#'.$name.' input[name=\''.$name.'\']");
        const colorpicker_'.$name.' = document.querySelector("div#'.$name.' input[name=\'colorpicker\']")
        colorpicker_'.$name.'.addEventListener("change", (event)=> {
            value_'.$name.'.value = event.target.value;
        });
        value_'.$name.'.addEventListener("input", (event)=> {
            colorpicker_'.$name.'.value = event.target.value;
        });
    </script>
    <style>
        #'.$name.' input[type="text"]:invalid {
            border: red solid 1.5px;
        }
        #'.$name.' input[type="text"] + .tooltip {
            border: red solid 1.5px;
            color: red;
            background: white;
            padding: 10px; 
            border-radius: 10px; 
            width: max-content;
            position: absolute; 
            top: -20%;
            left: calc(100% + 5px);
            z-index: 1; 
           
            display: none;
        }
        #'.$name.' input[type="text"]:invalid + .tooltip {
            display: block;
        }
    </style>
</tr>';
                case 'MIMAGE':
                    $values = strlen($value) ? explode('<>', $value) : [];
                    $imagesHTML = '';
                    foreach ($values as $val) {
                        $imagesHTML .= '
                            <div class="img_delete_box" draggable="true">
                                <img  src="'.$val.'" alt="'.$val.'">
                                <input type="button" value="Удалить"  class="delete">
                            </div>
                        ';
                    }
                    echo
'<tr valign="top">
    <td class="adm-detail-valign-top adm-detail-content-cell-l" >
        '.$title.':
    </td> 
    <td>
    <div id="'.$name.'" style="display: flex; flex-direction: column">
        <div style="width: max-content">
            <input type="file" accept="image/*, i" class="files" multiple>
        </div>
        
        <input type="text" style="display: none" name="'.$name.'" value="'.$value.'">
        
        <div class="images">'.$imagesHTML.'</div>
    </div>
    </td>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            document.querySelector("div#'.$name.' div span span").textContent = "Добавить файлы";
            
            const imagesContainer = document.querySelector("#'.$name.' .images");
            let draggedElement = null;
            
            imagesContainer.addEventListener("dragstart", function(event) {
                draggedElement = event.target.closest(".img_delete_box");
                event.dataTransfer.setData("text/plain", ""); // Needed for Firefox
            });
            
            imagesContainer.addEventListener("dragover", function(event) {
                event.preventDefault();
                const nearestElement = getNearestElement(event.clientX, event.clientY);
                const box = nearestElement.getBoundingClientRect();
                const mouseY = event.clientY;
                const offset = mouseY - box.top - box.height / 2;
                if (offset < 0) {
                    nearestElement.parentNode.insertBefore(draggedElement, nearestElement);
                } else {
                    nearestElement.parentNode.insertBefore(draggedElement, nearestElement.nextElementSibling);
                }
            });
            
            function getNearestElement(x, y) {
                const elements = Array.from(imagesContainer.querySelectorAll(".img_delete_box"));
                return elements.reduce((nearestElement, currentElement) => {
                    const box = currentElement.getBoundingClientRect();
                    const offsetX = x - box.left - box.width / 2;
                    const offsetY = y - box.top - box.height / 2;
                    const distance = Math.hypot(offsetX, offsetY);
                    if (distance < nearestElement.distance) {
                        return { distance, element: currentElement };
                    } else {
                        return nearestElement;
                    }
            }, { distance: Number.POSITIVE_INFINITY }).element;
            }
            
            imagesContainer.addEventListener("dragend", function() {
                draggedElement = null;
                // Вызов вашей функции после перетаскивания
                updateValue_'.$name.'();
            });
        });
        
        const value_'.$name.' = document.querySelector("div#'.$name.' input[name=\''.$name.'\']");
        const input_'.$name.' = document.querySelector("div#'.$name.' input.files");
        const images_'.$name.'_block = document.querySelector("div#'.$name.' div.images");
        
        input_'.$name.'.addEventListener("change", async (event)=> {
            const formData = new FormData();
            for (let i = 0; i < event.target.files.length; i++) {
                formData.append("files_"+i, event.target.files[i]);
            }
            const res = await fetch("/local/modules/legacy.settings/classes/general/addFiles.php",{
                method: "POST",
                body: formData
            }).then(res => res.json());
            
            res.pathes.forEach((path) => {
                images_'.$name.'_block.innerHTML += `
                    <div class="img_delete_box" draggable="true">
                        <img  src="${path}" alt="${path}">
                        <input type="button" value="Удалить" class="delete">
                    </div>
                `;
            });

            updateValue_'.$name.'();
            initDeletes_'.$name.'();
            document.querySelector("div#'.$name.' div span span").textContent = "Добавить файлы";
        });
        
        const initDeletes_'.$name.' = () => {
            const buttonsDelete = images_'.$name.'_block.querySelectorAll(".delete");
            buttonsDelete.forEach((buttonDelete) => {
                buttonDelete.onclick = (event) => {
                    event.target.parentNode.remove();
                    updateValue_'.$name.'();
                    document.querySelector("div#'.$name.' div span span").textContent = "Добавить файлы";
                };
            })
        }
        
        const updateValue_'.$name.' = () => {
            const imgs = images_'.$name.'_block.querySelectorAll("img");
            const arVals = [];
            imgs.forEach((img) => {
                arVals.push(img.alt);
            })
            
            value_'.$name.'.value = arVals.join("<>");
        }

        initDeletes_'.$name.'();
    </script>
    <style>
        #'.$name.' .img_delete_box {
            display: flex; 
            flex-direction: column; 
            align-items: center;
            padding: 5px 0 10px; 
            border: 1px solid #ccc;
            cursor: move;
        }
        
        #'.$name.' .img_delete_box:last-of-type {
            border-bottom: none;
        }
    
        #'.$name.' .img_delete_box img {
            width: 100%;
            margin-top: 10px;
        }
        
        #'.$name.' .img_delete_box input {
            width: 100%;
            margin-top: auto;
        }
    
        #'.$name.' .images {
            margin-top: 5px;
            background-color: #e0e8ea;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-gap: 5px;
        }
    </style>
</tr>';
                break;
                case 'IMAGE':
                    $displayImg = !$value ? 'display: none;' : '';
                    echo
'<tr valign="top">
    <td class="adm-detail-valign-top adm-detail-content-cell-l" >
        '.$title.':
    </td>
    <td>
        <div id="'.$name.'" style="display: flex; flex-direction: column">
            <div style="width: max-content">
                <input type="file" accept="image/*, i" class="file">
            </div>
            
            <input type="text" style="display: none" name="'.$name.'" value="'.$value.'">
            
            <img style="width: 50%; margin-top: 10px;' . $displayImg . '" src="'.$value.'" alt="'.$value.'">
            
            <input type="button" value="Удалить логотип" style="width: 50%; margin-top: 10px; '.$displayImg.'" src="'.$value.'" type="button" class="deleteLogo">
        </div>
    </td>
    <script>
        const value_'.$name.' = document.querySelector("div#'.$name.' input[name=\''.$name.'\']");
        const input_'.$name.' = document.querySelector("div#'.$name.' input.file");
        const img_'.$name.'  = document.querySelector("div#'.$name.' img");
        const delete_'.$name.' = document.querySelector("div#'.$name.' input.deleteLogo");
   
        input_'.$name.'.addEventListener("change", async (event)=> {
            const formData = new FormData();
            formData.append("file", event.target.files[0]);
            const res = await fetch("/local/modules/legacy.settings/classes/general/addFile.php",{
                method: "POST",
                body: formData
            }).then(res => res.json())
            value_'.$name.'.value = res.path;
            img_'.$name.'.src = res.path;
            img_'.$name.'.style.display = "block";
            delete_'.$name.'.style.display = "block";
        });
        if(delete_'.$name.'){
            delete_'.$name.'.addEventListener("click", () => {
                value_'.$name.'.value = "";
                img_'.$name.'.src = "";
                img_'.$name.'.style.display = "none";
                delete_'.$name.'.style.display = "none";
                document.querySelector("div#'.$name.' div span span").textContent = "Добавить файл";
                input_'.$name.'.value = "";
            });
        }
    </script>
</tr>';
                break;
                case 'TEXTAREA':
                    $rows = $arTabOption['ROWS_COUNT'];
                    echo
'<tr>
    <td class="adm-detail-valign-top adm-detail-content-cell-l" width="50%" style="    vertical-align: middle;">
        '.$title.':
    </td>
    <td width="50%" class="adm-detail-content-cell-r">
        <div id="'.$name.'" style="display:flex;">
            <textarea type="text" name="'.$name.'" rows="'.$rows.'" style="width: 95%; resize: none;">'.$value.'</textarea>
        </div>
    </td>
</tr>';
                break;
                case 'TEXT':
                default:
                    echo
'<tr>
    <td class="adm-detail-valign-top adm-detail-content-cell-l" width="50%" style="    vertical-align: middle;">
        '.$title.':
    </td>
    <td width="50%" class="adm-detail-content-cell-r">
        <div id="'.$name.'" style="display:flex;">
            <input type="text" name="'.$name.'" value="'.$value.'" style="width: 95%;">
        </div>
    </td>
</tr>';
                    break;
            }
    }
}
?>