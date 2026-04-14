/*********** function to check all checkboxes functionality ************/
jQuery(document).ready(function() {
    jQuery(':checkbox').click(function() {
        var totalcount = jQuery(":checkbox[id='select1']").length;
        var checkedcount = jQuery(":checkbox[id='select1']:checked").length;
        if (totalcount == checkedcount) {
            jQuery(":checkbox[id='selectAllChildCheckboxs']").attr('checked', true);
        } else {
            jQuery(":checkbox[id='selectAllChildCheckboxs']").attr('checked', false);
        }
    });
});

function checkAll(field) {
    if (field.value) {
        field.checked = true;
    } else {
        for (i = 0; i < field.length; i++)
            field[i].checked = true;
    }
}

function uncheckAll(field) {
    if (field.value) {
        field.checked = false
    } else {
        for (i = 0; i < field.length; i++)
            field[i].checked = false;
    }
}

function GetAction(val, field, id) {

    if (val == true)
        checkAll(id);
    else
        uncheckAll(id);
}



function isSelected(frm, field, actType) {
    document.getElementById('submit').value = actType;
    var isAnySelected = false;
    //if single row is exists
    if (field.value != undefined) {
        if (field.checked == true)
            isAnySelected = true;
    } else {
        for (i = 0; i < field.length; i++)
            if (field[i].checked == true) {
                isAnySelected = true;
            }
    }
    if (isAnySelected == false) {
        alert('Please select atleast one record');
        return false;
    } else if (actType == 'del') {
        //createCustomAlert('Are you sure you want to delete the record?');
        if (!confirm('Are you sure you want to delete the record?'))
            return false;
        //return false;
    }

}
function ischeckboxSelected(frm, field, model) {

    //alert(frm);alert(field);alert(model);
    actType = document.getElementById(model + 'Status').value;
    field = jQuery(":checkbox[id='select1']");
    if (actType == '') {
        alert('Please select action you want to perform.');
        return false;
    } else {
        var isAnySelected = false;

        //alert(field);
        if (field.value != undefined) {
            if (field.checked == true)
                isAnySelected = true;
        } else {
            //alert(field.length);
            for (i = 0; i < field.length; i++)
                if (field[i].checked == true) {
                    isAnySelected = true;
                }

        }
        if (isAnySelected == false) {
            alert('Please select atleast one record');
            return false;
        } else if (actType == 'del') {
            //createCustomAlert('Are you sure you want to delete the record?');
            if (!confirm('Are you sure you want to delete the record?'))
                return false;
            //return false;
        }
    }
}

//custom alert box option
/* This script and many more are available free online at
 The JavaScript Source!! http://javascript.internet.com
 Created by: Steve Chipman | http://slayeroffice.com/ */

// constants to define the title of the alert and button text.
var ALERT_TITLE = "Oops!";
var ALERT_BUTTON_TEXT = "Close";

// over-ride the alert method only if this a newer browser.
// Older browser will see standard alerts
if (document.getElementById) {
    /*window.alert = function(txt) {
     createCustomAlert(txt);
     }*/
}

function createCustomAlert(txt) {
    // shortcut reference to the document object

    d = document;

    // if the modalContainer object already exists in the DOM, bail out.
    if (d.getElementById("modalContainer"))
        return;

    // create the modalContainer div as a child of the BODY element
    mObj = d.getElementsByTagName("body")[0].appendChild(d.createElement("div"));
    mObj.id = "modalContainer";
    // make sure its as tall as it needs to be to overlay all the content on the page
    mObj.style.height = document.documentElement.scrollHeight + "px";

    // create the DIV that will be the alert 
    alertObj = mObj.appendChild(d.createElement("div"));
    alertObj.id = "alertBox";
    // MSIE doesnt treat position:fixed correctly, so this compensates for positioning the alert
    if (d.all && !window.opera)
        alertObj.style.top = document.documentElement.scrollTop + "px";
    // center the alert box
    //alertObj.style.left = (d.documentElement.scrollWidth - alertObj.offsetWidth)/2 + "px";
    alertObj.style.left = (window.screen.width / 2) + "px";

    // create an H1 element as the title bar
    h1 = alertObj.appendChild(d.createElement("h1"));
    h1.appendChild(d.createTextNode(ALERT_TITLE));

    // create a paragraph element to contain the txt argument
    msg = alertObj.appendChild(d.createElement("p"));
    msg.innerHTML = txt;

    // create an anchor element to use as the confirmation button.
    btn = alertObj.appendChild(d.createElement("a"));
    btn.id = "closeBtn";
    btn.appendChild(d.createTextNode(ALERT_BUTTON_TEXT));
    btn.href = "#";
    // set up the onclick event to remove the alert when the anchor is clicked
    btn.onclick = function() {
        removeCustomAlert();
        return false;
    }
}

// removes the custom alert from the DOM
function removeCustomAlert() {
    document.getElementsByTagName("body")[0].removeChild(document.getElementById("modalContainer"));
}


