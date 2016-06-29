//<![CDATA[
function set_jump(status, url, number){
	$("#status").val(status);
	$("#url").val(url);
	$("#number").val(number);
	$("#teacher").val(number);
	$("#form3").attr("action","set_log.php");
	$("#form3").submit();
}

function jump(url, data){
	$("#data").val(data);
	$("#tflag2").val($("#teacher:checked").val());
	$("#form5").attr("target", "");
	$("#form5").attr("action",url);
	$("#form5").submit();
}

function check_password(){
	var tmp = $("#pass").val();
	var len = tmp.length;
	if(len == 0){
		$("#password_error").html(M.util.get_string('js_error_password', 'vcubemeeting'));
		$("#password_error").attr("class", "error");
		return false;
	}
	if(tmp.match(/^[a-zA-Z0-9]+$/) != null ){
		if( (tmp.length < 6) || (tmp.length > 16)){
			$("#password_error").html(M.util.get_string('js_error_password', 'vcubemeeting'));
			$("#password_error").attr("class", "error");
			return false;
		}else{
			return true;
		}
	}else{
		$("#password_error").html(M.util.get_string('js_error_password', 'vcubemeeting'));
		$("#password_error").attr("class", "error");
		return false;
	}
}

function stop_confernce(){
	ret = confirm(M.util.get_string('js_stop_confernce', 'vcubemeeting'));
	return ret;
}
//]]>