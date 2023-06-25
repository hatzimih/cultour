var dragged;

function enableDragging() {
    $(".draggable").each(function(index, elem) {
        elem.addEventListener("dragstart", (event) => {
            // store a ref. on the dragged elem
            dragged = event.target;
            // make it half transparent
            event.target.classList.add("dragging");
        });
        elem.addEventListener("dragend", (event) => {
            // reset the transparency
            event.target.classList.remove("dragging");
        });
    });
    $(".dropzone").each(function(index, elem) {
        elem.addEventListener("dragover", (event) => {
            // prevent default to allow drop
            event.preventDefault();
        }, false);
        elem.addEventListener("dragenter", (event) => {
            // highlight potential drop target when the draggable element enters it
            if (event.target.classList.contains("dropzone")) {
                event.target.classList.add("dragover");
            }
        });
        elem.addEventListener("dragleave", (event) => {
            // reset background of potential drop target when the draggable element leaves it
            if (event.target.classList.contains("dropzone")) {
                event.target.classList.remove("dragover");
            }
        });
        elem.addEventListener("drop", (event) => {
            // prevent default action (open as link for some elements)
            event.preventDefault();
            // move dragged element to the selected drop target
            if (event.target.classList.contains("dropzone")) {
                event.target.classList.remove("dragover");
                event.target.appendChild(dragged);
            }
        });
    });
}