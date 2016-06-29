/**
 * form select onchange action
 */

// ---- load ---- //
//new activity//
if(!$("#id_roomdata").val()){
        $('#id_ondemand  option').remove();
//edit activity//
}else{
	/*
    var keep = $('#id_ondemand').val();
    change_room();
    $('#id_ondemand').val(keep);
    */
}

// ---- change room ---- //
function change_room(){
    var now_select = $("#id_roomdata").val();
    $.ajax({
        url: '/mod/vcubeseminar/ajax_receive.php',
        type: 'post',
        datatype:'jsonp',
        data:{'select_room' : now_select },
        success: function(data){
            var room_data = $("#id_roomdata").val().split('_');
            $("[name=vcubeseminardomainid]").val(room_data[0]);
            $("[name=roomid]").val(room_data[1]);
            var ondemand_list = JSON.parse(data);
            $('#id_ondemand  option').remove();
            if(ondemand_list.length >0){
                for(var i=0; i<ondemand_list.length;i++){
                    $("#id_ondemand").append($("<option>").val(ondemand_list[i]['key']).text(ondemand_list[i]['name']));
                }
            }
        },
        error:function(){
            alert("failure");
        }
    });

}
