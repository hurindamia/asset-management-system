// ---------------- RAM ----------------

let ramCount = 1;

function addRam(){

ramCount++;

let container = document.getElementById("ramContainer");

let ramHTML = `
<div class="ram-item">

<div class="item-header">
<div class="ram-title">RAM ${ramCount}</div>
<button type="button" class="remove-btn" onclick="removeItem(this)">Cancel</button>
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

container.insertAdjacentHTML("beforeend", ramHTML);

}

function removeRam(button){

let item = button.parentElement;

item.remove();

updateRamTitles();

}

function updateRamTitles(){

let items = document.querySelectorAll(".ram-item");

items.forEach((item,index)=>{
item.querySelector(".ram-title").innerText = "RAM " + (index+1);
});

}


// ---------------- STORAGE ----------------

let storageCount = 1;

function addStorage(){

storageCount++;

let container = document.getElementById("storageContainer");

let storageHTML = `
<div class="storage-item">

<div class="item-header">
<div class="storage-title">Storage ${storageCount}</div>
<button type="button" class="remove-btn" onclick="removeItem(this)">Cancel</button>
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

container.insertAdjacentHTML("beforeend", storageHTML);

}

function removeStorage(button){

let item = button.parentElement;

item.remove();

updateStorageTitles();

}

function updateStorageTitles(){

let items = document.querySelectorAll(".storage-item");

items.forEach((item,index)=>{
item.querySelector(".storage-title").innerText = "Storage " + (index+1);
});

}


// ---------------- MONITOR ----------------

let monitorCount = 1;

function addMonitor(){

monitorCount++;

let container = document.getElementById("monitorContainer");

let monitorHTML = `
<div class="monitor-item">

<div class="item-header">
<div class="monitor-title">Monitor ${monitorCount}</div>
<button type="button" class="remove-btn" onclick="removeItem(this)">Cancel</button>
</div>

<div class="form-row">
<label>Model</label>
<input type="text" name="monitor_model[]" placeholder="Monitor Model" required>
</div>

<div class="form-row">
<label>Size</label>
<input type="text" name="monitor_size[]" placeholder="Monitor Size" required>
</div>

<div class="form-row">
<label>Serial</label>
<input type="text" name="monitor_serial[]" placeholder="Serial Number" required>
</div>

</div>
`;

container.insertAdjacentHTML("beforeend", monitorHTML);

}

function removeMonitor(button){

let item = button.parentElement;

item.remove();

updateMonitorTitles();

}

function updateMonitorTitles(){

let items = document.querySelectorAll(".monitor-item");

items.forEach((item,index)=>{
item.querySelector(".monitor-title").innerText = "Monitor " + (index+1);
});

}

function removeItem(button){
const item = button.closest(".ram-item, .storage-item, .monitor-item, .software-item");
if(item){
item.remove();
}
}



// ---------------- SOFTWARE ----------------

function getSoftwareCount(){
    return document.querySelectorAll("#softwareContainer .software-item").length;
}

function addSoftware(){

    let container = document.getElementById("softwareContainer");

    let softwareCount = getSoftwareCount() + 1;

    let softwareHTML = `
    <div class="software-item">

    <div class="item-header">
    <div class="software-title">Software ${softwareCount}</div>
    <button type="button" class="remove-btn" onclick="removeSoftware(this)">Cancel</button>
    </div>

    <div class="form-row">
    ${softwareCount === 1 ? '<label>Software Name</label>' : ''}
    <input type="text" name="software[]" placeholder="Enter Software">
    </div>

    </div>
    `;

    container.insertAdjacentHTML("beforeend", softwareHTML);

    updateSoftwareTitles(); // 🔥 ensure correct numbering
}


// REMOVE
function removeSoftware(button){

    let item = button.closest(".software-item");
    item.remove();

    updateSoftwareTitles();
}


// UPDATE NUMBERING
function updateSoftwareTitles(){

    let items = document.querySelectorAll("#softwareContainer .software-item");

    items.forEach((item,index)=>{

        // update title
        item.querySelector(".software-title").innerText = "Software " + (index+1);

        // handle label
        let formRow = item.querySelector(".form-row");
        let existingLabel = formRow.querySelector("label");

        if(index === 0){
            if(!existingLabel){
                let label = document.createElement("label");
                label.innerText = "Software Name";
                formRow.insertBefore(label, formRow.firstChild);
            }
        }else{
            if(existingLabel){
                existingLabel.remove();
            }
        }

    });

}

