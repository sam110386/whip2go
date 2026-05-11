/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


function AddDealerNote(userid){
   jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"user_note/user_notes/add", {userid:userid},function (data) {
        jQuery.unblockUI();
        $("#myModal .modal-content").html(data);
        $("#myModal").modal('show').find('.modal-dialog').css('width','750px');;
    });
}


function saveDealerNote(){
   jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    let data=$("#addNoteForm").serialize();
    $.post(SITE_URL+"user_note/user_notes/save", data,function (data) {
        jQuery.unblockUI();
        $("#myModal .modal-content").html('');
        $("#myModal").modal('hide');
        location.reload();
    });
}


function AddNewNote(userid){
   jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"admin/user_note/user_notes/add", {userid:userid},function (data) {
        jQuery.unblockUI();
        $("#myModal .modal-content").html(data);
        $("#myModal").modal('show').find('.modal-dialog').css('width','750px');;
    });
}


function saveNote(){
   jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    let data=$("#addNoteForm").serialize();
    $.post(SITE_URL+"admin/user_note/user_notes/save", data,function (data) {
        jQuery.unblockUI();
        $("#myModal .modal-content").html('');
        $("#myModal").modal('hide');
        location.reload();
    });
}

function AddCloudNote(userid){
   jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"cloud/user_note/user_notes/add", {userid:userid},function (data) {
        jQuery.unblockUI();
        $("#myModal .modal-content").html(data);
        $("#myModal").modal('show').find('.modal-dialog').css('width','750px');;
    });
}


function saveCloudNote(){
   jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    let data=$("#addNoteForm").serialize();
    $.post(SITE_URL+"cloud/user_note/user_notes/save", data,function (data) {
        jQuery.unblockUI();
        $("#myModal .modal-content").html('');
        $("#myModal").modal('hide');
        location.reload();
    });
}


