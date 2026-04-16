/*
|--------------------------------------------------------------------------
| Dynamic form item helpers
|--------------------------------------------------------------------------
| The add/edit asset screens use the same repeatable sections for RAM,
| storage, monitor, and software. These helpers keep the markup, numbering,
| and remove logic in one place.
*/

const DYNAMIC_ITEM_CONFIG = {
    ram: {
        containerId: "ramContainer",
        itemClass: "ram-item",
        titleClass: "ram-title",
        label: "RAM",
    },
    storage: {
        containerId: "storageContainer",
        itemClass: "storage-item",
        titleClass: "storage-title",
        label: "Storage",
    },
    monitor: {
        containerId: "monitorContainer",
        itemClass: "monitor-item",
        titleClass: "monitor-title",
        label: "Monitor",
    },
    software: {
        containerId: "softwareContainer",
        itemClass: "software-item",
        titleClass: "software-title",
        label: "Software",
    },
};

const WINDOWS_MAX_ITEMS = 10;
const WINDOWS_OPTIONS = ["Windows 7", "Windows 8.1", "Windows 10", "Windows 11", "Mac OS"];

function getDynamicContainer(type){
    const config = DYNAMIC_ITEM_CONFIG[type];
    return config ? document.getElementById(config.containerId) : null;
}

function getDynamicCount(type){
    const container = getDynamicContainer(type);
    const config = DYNAMIC_ITEM_CONFIG[type];

    if(!container || !config){
        return 0;
    }

    return container.querySelectorAll(`.${config.itemClass}`).length;
}

function getMonitorRequirement(){
    const assetTypeInput = document.querySelector('input[name="asset_type"]');
    const isLaptop = assetTypeInput && assetTypeInput.value === "Laptop";
    return isLaptop ? "" : "required";
}

function buildDynamicMarkup(type, index){
    if(type === "ram"){
        return `
<div class="ram-item">
<div class="item-header">
<div class="ram-title">RAM ${index}</div>
<button type="button" class="remove-btn item-remove-btn" onclick="removeItem(this)" title="Remove RAM">X</button>
</div>
<div class="form-row">
<label>RAM Size</label>
<select name="ram_size[]" required>
<option value="">Select RAM</option>
<option>2 GB</option>
<option>4 GB</option>
<option>8 GB</option>
<option>16 GB</option>
<option>32 GB</option>
<option>64 GB</option>
</select>
</div>
</div>
`;
    }

    if(type === "storage"){
        return `
<div class="storage-item">
<div class="item-header">
<div class="storage-title">Storage ${index}</div>
<button type="button" class="remove-btn item-remove-btn" onclick="removeItem(this)" title="Remove storage">X</button>
</div>
<div class="form-row">
<label>Model</label>
<input type="text" name="hdd_model[]" placeholder="HDD Model" required>
</div>
<div class="form-row">
<label>Capacity</label>
<input type="text" name="hdd_capacity[]" placeholder="Capacity" required>
</div>
<div class="form-row">
<label>Serial</label>
<input type="text" name="hdd_serial[]" placeholder="Serial Number" required>
</div>
</div>
`;
    }

    if(type === "monitor"){
        const required = getMonitorRequirement();

        return `
<div class="monitor-item">
<div class="item-header">
<div class="monitor-title">Monitor ${index}</div>
<button type="button" class="remove-btn item-remove-btn" onclick="removeItem(this)" title="Remove monitor">X</button>
</div>
<div class="form-row">
<label>Model</label>
<input type="text" name="monitor_model[]" placeholder="Monitor Model" ${required}>
</div>
<div class="form-row">
<label>Size</label>
<input type="text" name="monitor_size[]" placeholder="Monitor Size" ${required}>
</div>
<div class="form-row">
<label>Serial</label>
<input type="text" name="monitor_serial[]" placeholder="Serial Number" ${required}>
</div>
</div>
`;
    }

    if(type === "software"){
        return `
<div class="software-item">
<div class="item-header">
<div class="software-title">Software ${index}</div>
<button type="button" class="remove-btn item-remove-btn" onclick="removeSoftware(this)" title="Remove software">X</button>
</div>
<div class="form-row">
<input type="text" name="software[]" placeholder="Enter Software">
</div>
</div>
`;
    }

    return "";
}

function appendDynamicItem(type){
    const container = getDynamicContainer(type);

    if(!container){
        return;
    }

    const index = getDynamicCount(type) + 1;
    container.insertAdjacentHTML("beforeend", buildDynamicMarkup(type, index));
    renumberDynamicItems(type);
}

function renumberDynamicItems(type){
    const container = getDynamicContainer(type);
    const config = DYNAMIC_ITEM_CONFIG[type];

    if(!container || !config){
        return;
    }

    const items = container.querySelectorAll(`.${config.itemClass}`);

    items.forEach((item, index) => {
        const title = item.querySelector(`.${config.titleClass}`);

        if(title){
            title.innerText = `${config.label} ${index + 1}`;
        }

        if(type === "software"){
            const formRow = item.querySelector(".form-row");
            const existingLabel = formRow ? formRow.querySelector("label") : null;

            if(index === 0){
                if(formRow && !existingLabel){
                    const label = document.createElement("label");
                    label.innerText = "Software Name";
                    formRow.insertBefore(label, formRow.firstChild);
                }
            } else if(existingLabel){
                existingLabel.remove();
            }
        }
    });
}

function getItemTypeFromButton(button){
    if(button.closest(".ram-item")){
        return "ram";
    }

    if(button.closest(".storage-item")){
        return "storage";
    }

    if(button.closest(".monitor-item")){
        return "monitor";
    }

    if(button.closest(".software-item")){
        return "software";
    }

    return null;
}

function removeItem(button){
    const type = getItemTypeFromButton(button);
    const item = button.closest(".ram-item, .storage-item, .monitor-item, .software-item");

    if(!item){
        return;
    }

    item.remove();

    if(type){
        renumberDynamicItems(type);
    }
}

// Backward-compatible wrappers used by existing inline button handlers.
function addRam(){
    appendDynamicItem("ram");
}

function addStorage(){
    appendDynamicItem("storage");
}

function addMonitor(){
    appendDynamicItem("monitor");
}

function addSoftware(){
    appendDynamicItem("software");
}

function getSoftwareCount(){
    return getDynamicCount("software");
}

function removeRam(button){
    removeItem(button);
}

function removeStorage(button){
    removeItem(button);
}

function removeMonitor(button){
    removeItem(button);
}

function removeSoftware(button){
    removeItem(button);
}

function updateRamTitles(){
    renumberDynamicItems("ram");
}

function updateStorageTitles(){
    renumberDynamicItems("storage");
}

function updateMonitorTitles(){
    renumberDynamicItems("monitor");
}

function updateSoftwareTitles(){
    renumberDynamicItems("software");
}

function buildWindowsOptionsHtml(selected = ""){
    const selectedValue = String(selected || "").trim();
    let options = '<option value="">Select Windows</option>';

    WINDOWS_OPTIONS.forEach((option) => {
        const isSelected = option === selectedValue ? " selected" : "";
        options += `<option value="${option}"${isSelected}>${option}</option>`;
    });

    return options;
}

function toggleWindowsSerialField(selectEl){
    const windowsItem = selectEl.closest(".windows-item");
    if(!windowsItem){
        return;
    }

    const serialRow = windowsItem.querySelector(".windows-serial-row");
    const serialInput = serialRow ? serialRow.querySelector('input[name="windows_serial[]"]') : null;
    const hasOs = String(selectEl.value || "").trim() !== "";

    if(serialRow){
        serialRow.style.display = hasOs ? "" : "none";
    }

    if(serialInput){
        if(hasOs){
            serialInput.disabled = false;
            serialInput.setAttribute("required", "required");
        } else {
            serialInput.disabled = true;
            serialInput.removeAttribute("required");
            serialInput.value = "";
        }
    }
}

function renumberWindowsItems(){
    const container = document.getElementById("windowsContainer");
    if(!container){
        return;
    }

    const items = container.querySelectorAll(".windows-item");

    items.forEach((item, index) => {
        const title = item.querySelector(".windows-title");
        const select = item.querySelector('select[name="window__os[]"]');
        const removeBtn = item.querySelector(".remove-btn");

        if(title){
            title.textContent = `Windows ${index + 1}`;
        }

        if(select){
            if(index === 0){
                select.setAttribute("required", "required");
            } else {
                select.removeAttribute("required");
            }
            toggleWindowsSerialField(select);
        }

        if(removeBtn){
            removeBtn.style.display = items.length > 1 ? "" : "none";
        }
    });
}

function addWindows(){
    const container = document.getElementById("windowsContainer");
    if(!container){
        return;
    }

    const currentCount = container.querySelectorAll(".windows-item").length;
    if(currentCount >= WINDOWS_MAX_ITEMS){
        alert(`Maximum ${WINDOWS_MAX_ITEMS} Windows entries per device.`);
        return;
    }

    const index = currentCount + 1;
    const item = document.createElement("div");
    item.className = "windows-item";
    item.innerHTML = `
<div class="item-header">
<div class="windows-title">Windows ${index}</div>
<button type="button" class="remove-btn item-remove-btn" onclick="removeWindows(this)" title="Remove Windows">X</button>
</div>
<div class="form-row">
<label>Operating System</label>
<select name="window__os[]" onchange="toggleWindowsSerialField(this)">
${buildWindowsOptionsHtml("")}
</select>
</div>
<div class="form-row windows-serial-row" style="display:none;">
<label>Windows Serial / Key</label>
<input type="text" name="windows_serial[]" placeholder="Windows Serial / Product Key" disabled>
</div>
`;

    container.appendChild(item);
    renumberWindowsItems();
}

function removeWindows(button){
    const item = button.closest(".windows-item");
    const container = document.getElementById("windowsContainer");
    if(!item || !container){
        return;
    }

    const totalItems = container.querySelectorAll(".windows-item").length;
    if(totalItems <= 1){
        return;
    }

    item.remove();
    renumberWindowsItems();
}

function initWindowsSection(){
    renumberWindowsItems();
}

window.toggleWindowsSerialField = toggleWindowsSerialField;
window.addWindows = addWindows;
window.removeWindows = removeWindows;
window.initWindowsSection = initWindowsSection;
