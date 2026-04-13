(function(){
    "use strict";

    function cleanText(value){
        return String(value || "").replace(/\s+/g, " ").trim();
    }

    function unique(list){
        return [...new Set(list)];
    }

    function getFieldLabel(field){
        const row = field.closest(".form-row");
        const label = row ? row.querySelector("label") : null;

        if(label){
            return cleanText(label.textContent).replace(/[:*]+$/g, "");
        }

        if(field.dataset.label){
            return cleanText(field.dataset.label);
        }

        if(field.placeholder){
            return cleanText(field.placeholder);
        }

        if(field.name){
            return cleanText(field.name.replace(/[_\[\]]+/g, " "));
        }

        return "This field";
    }

    function getFieldContext(field){
        const parts = [];
        const deviceTitle = field.closest(".device-block")?.querySelector(".device-block-title");
        const sectionTitle = field.closest(".section")?.querySelector(".section-title");
        const itemTitle = field
            .closest(".ram-item, .storage-item, .monitor-item, .software-item")
            ?.querySelector(".ram-title, .storage-title, .monitor-title, .software-title");

        if(deviceTitle){
            parts.push(cleanText(deviceTitle.textContent));
        }

        if(sectionTitle){
            parts.push(cleanText(sectionTitle.textContent).replace("(optional)", "").trim());
        }

        if(itemTitle){
            parts.push(cleanText(itemTitle.textContent));
        }

        return parts.filter(Boolean).join(" > ");
    }

    function getValidityMessage(field){
        const label = getFieldLabel(field);
        const context = getFieldContext(field);
        const prefix = context ? context + " - " : "";

        if(field.validity.valueMissing){
            return prefix + label + " is required.";
        }

        if(field.validity.typeMismatch && field.type === "email"){
            return prefix + label + " must be a valid email address.";
        }

        if(field.validity.patternMismatch){
            return prefix + label + " format is invalid.";
        }

        if(field.validity.tooShort){
            return prefix + label + " is too short.";
        }

        if(field.validity.tooLong){
            return prefix + label + " is too long.";
        }

        return prefix + label + " is invalid.";
    }

    function clearValidationUi(form){
        form.querySelectorAll(".error-input").forEach((el) => el.classList.remove("error-input"));
        form.querySelectorAll(".section-error").forEach((el) => el.classList.remove("section-error"));
    }

    function openErrorPopup(popupId, listId, errors){
        const popup = document.getElementById(popupId);
        const list = document.getElementById(listId);

        if(!popup || !list){
            return;
        }

        list.innerHTML = errors.map((error) => `<li>${error}</li>`).join("");
        popup.style.display = "flex";
    }

    function closeErrorPopup(popupId){
        const popup = document.getElementById(popupId || "errorPopup");
        if(popup){
            popup.style.display = "none";
        }
    }

    function trimInputValue(field){
        if(field.tagName === "TEXTAREA" || field.type === "text" || field.type === "email" || field.type === "search" || field.type === "tel"){
            field.value = field.value.trim();
        }
    }

    function validateForm(form, options){
        const config = Object.assign(
            {
                popupId: "errorPopup",
                errorListId: "errorList",
                requireDeviceType: false,
            },
            options || {}
        );

        clearValidationUi(form);

        const errors = [];
        let firstError = null;

        if(config.requireDeviceType){
            const blocks = form.querySelectorAll(".device-block");

            if(blocks.length === 0){
                errors.push("Please add at least one device before saving.");
            }

            blocks.forEach((block, index) => {
                const select = block.querySelector(".device-type-select");
                const hiddenType = block.querySelector('input[id^="assetType_"]');

                if(typeof window.syncMonitorRequirements === "function" && select && hiddenType){
                    const idx = hiddenType.id.replace("assetType_", "");
                    window.syncMonitorRequirements(idx, select.value);
                }

                if(select && !select.disabled && !select.value){
                    errors.push(`Device ${index + 1} - Please select a device type.`);
                    if(!firstError){
                        firstError = select;
                    }
                }
            });
        }

        form.querySelectorAll("input, select, textarea").forEach((field) => {
            if(field.disabled || field.type === "hidden" || !field.willValidate){
                return;
            }

            trimInputValue(field);

            if(!field.checkValidity()){
                field.classList.add("error-input");
                const section = field.closest(".section");
                if(section){
                    section.classList.add("section-error");
                }
                errors.push(getValidityMessage(field));
                if(!firstError){
                    firstError = field;
                }
            }
        });

        const uniqueErrors = unique(errors);

        if(uniqueErrors.length > 0){
            openErrorPopup(config.popupId, config.errorListId, uniqueErrors);

            if(firstError){
                firstError.scrollIntoView({ behavior: "smooth", block: "center" });
                if(typeof firstError.focus === "function"){
                    firstError.focus({ preventScroll: true });
                }
            }

            return false;
        }

        return true;
    }

    function attachValidation(formId, options){
        const form = document.getElementById(formId);
        if(!form){
            return;
        }

        form.addEventListener("submit", function(event){
            if(!validateForm(form, options)){
                event.preventDefault();
            }
        });
    }

    window.closeErrorPopup = closeErrorPopup;
    window.AssetFormValidation = {
        attachValidation: attachValidation,
        validateForm: validateForm,
    };

    document.addEventListener("DOMContentLoaded", function(){
        attachValidation("hardwareForm", {
            popupId: "errorPopup",
            errorListId: "errorList",
            requireDeviceType: true,
        });

        attachValidation("editAssetForm", {
            popupId: "editErrorPopup",
            errorListId: "editErrorList",
            requireDeviceType: false,
        });
    });
})();

