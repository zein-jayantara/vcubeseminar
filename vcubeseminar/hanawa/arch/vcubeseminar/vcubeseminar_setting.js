$(document).ready(function() {
    vcubeseminar_domainlist_get();
});

var vcubeseminar_domainlist_get = function (targetid) {
    sendata = {
        mode : 'get',
        sesskey : $('[name=sesskey]').val()
    };
    if(targetid) {
        sendata = {
            mode : 'get',
            id : targetid,
            sesskey : $('[name=sesskey]').val()
        };
    }

    $.ajax({
        url: '/mod/vcubeseminar/domain.php',
        type: 'get',
        datatype:'jsonp',
        data: sendata,
        success: function(data) {
            json_data = JSON.parse(data);
            $('[id^=vcubeseminarcanceldomain]').click();
            if(json_data['operation'] == 'getall') {
                $('.serminardomains tbody').empty();
            }
            var element_str = '';
            for(var domain_index = 0; domain_index < json_data['domains'].length; domain_index++) {
                element_str  = '<tr class="domainrow' + json_data['domains'][domain_index]['id'] + '"><form>';
                element_str += '<td><input type="hidden" name="id[' + json_data['domains'][domain_index]['id'] + ']" value="' + json_data['domains'][domain_index]['id'] + '" />';
                element_str += '<span class="vcseminar_domain_list_view">' + json_data['domains'][domain_index]['alias'] + '</span>';
                element_str += '<span class="vcseminar_domain_list_edit" style="display:none"><input type="text" name="alias[' + json_data['domains'][domain_index]['id'] + ']" value="' + json_data['domains'][domain_index]['alias'] + '" /></span>';
                element_str += '</td><td>';
                element_str += '<span class="vcseminar_domain_list_view">' + json_data['domains'][domain_index]['domain'] + '</span>';
                element_str += '<span class="vcseminar_domain_list_edit" style="display:none"><input type="text" name="vcseminar_domain[' + json_data['domains'][domain_index]['id'] + ']" value="' + json_data['domains'][domain_index]['domain'] + '" /></span>';
                element_str += '</td><td>';
                element_str += '<span class="vcseminar_domain_list_view">' + json_data['domains'][domain_index]['account'] + '</span>';
                element_str += '<span class="vcseminar_domain_list_edit" style="display:none"><input type="text" name="vcseminar_id[' + json_data['domains'][domain_index]['id'] + ']" value="' + json_data['domains'][domain_index]['account'] + '" /></span>';
                element_str += '</td><td>';
                element_str += '<span class="vcseminar_domain_list_view">********</span>';
                element_str += '<span class="vcseminar_domain_list_edit" style="display:none"><input type="password" name="vcseminar_password[' + json_data['domains'][domain_index]['id'] + ']" value="' + json_data['domains'][domain_index]['password'] + '" /></span>';
                element_str += '</td><td>';
                element_str += '<span class="vcseminar_domain_list_view"><input type="button" class="form-edit" title="' + json_data['strings']['edit'] + '" id="vcubeseminareditdomain' + domain_index + '" value="' + json_data['strings']['edit'] + '" /></span>';
                element_str += '<span class="vcseminar_domain_list_edit" style="display:none"><input type="submit" class="form-submit" title="' + json_data['strings']['update'] + '" id="vcubeseminarsubmitdomain' + domain_index + '" value="' + json_data['strings']['update'] + '" disabled="disabled" /></span>';
                element_str += '</td><td>';
                element_str += '<span class="vcseminar_domain_list_view"><input type="button" class="form-delete" title="' + json_data['strings']['delete'] + '" id="vcubeseminardeletedomain' + domain_index + '" value="' + json_data['strings']['delete'] + '" /></span>';
                element_str += '<span class="vcseminar_domain_list_edit" style="display:none"><input type="reset" class="form-reset" title="' + json_data['strings']['cancel'] + '" id="vcubeseminarcanceldomain' + domain_index + '" value="' + json_data['strings']['cancel'] + '"></span>';
                element_str += '</td></form></tr>';

                if($('.domainrow' + json_data['domains'][domain_index]['id']).length > 0) {
                    $('.domainrow' + json_data['domains'][domain_index]['id']).replaceWith(element_str);
                }
                else {
                    $('.serminardomains tbody').append(element_str);
                }
                $('[name=sesskey]').val(json_data['result']['sesskey']);
            }
            // Edit
            $('table .form-edit').off('click.form-edit').on('click.form-edit', function() {
                $('[id^=vcubeseminarcanceldomain]').click();
                $('.domainlist_add_row :input').attr('disabled', 'disabled');
                vcubeseminar_domainlist_validate($(this).parent().parent().parent());
                $('.vcseminar_domain_list_view', $(this).parent().parent().parent()).hide();
                $('.vcseminar_domain_list_edit', $(this).parent().parent().parent()).show();
                return false;
            });
            // Cancel
            $('table .form-reset').off('click.form-reset').on('click.form-reset', function() {
                $('.domainlist_add_row :input').removeAttr('disabled', 'disabled');
                $('.vcseminar_domain_list_edit', $(this).parent().parent().parent()).hide();
                $('.vcseminar_domain_list_view', $(this).parent().parent().parent()).show();
                vcubeseminar_domainlist_validate($(this).parent().parent().parent());
                vcubeseminar_domainlist_validate($('.domainlist_add_row'));
                return true;
            });
            // Submit
            $('table .form-submit').off('click.form-submit').on('click.form-submit', function() {
                vcubeseminar_domainlist_send($(this).parent().parent().parent());
                return false;
            });
            // Delete
            $('table .form-delete').off('click.form-delete').on('click.form-delete', function() {
                vcubeseminar_domainlist_delete($(this).parent().parent().parent());
                return false;
            });
            $('table [name^=vcseminar_domain], table [name^=vcseminar_id], table [name^=vcseminar_password]').off('keyup.form-validate focus.form-validate blur.form-validate').on('keyup.form-validate focus.form-validate blur.form-validate', function() {
                vcubeseminar_domainlist_validate($(this).parent().parent().parent());
            });
        },
        error:function(){
            console.log("failure");
        }
    });
};

var vcubeseminar_domainlist_send = function (target_element) {
    $.ajax({
        url: '/mod/vcubeseminar/domain.php?mode=send&sesskey=' + $('[name=sesskey]').val(),
        type: 'post',
        datatype:'jsonp',
        data: {
            id : $('[name^=id]', target_element).val(),
            alias : $("[name^=alias]", target_element).val(),
            vcseminar_domain : $("[name^=vcseminar_domain]", target_element).val(),
            vcseminar_id : $('[name^=vcseminar_id]', target_element).val(),
            vcseminar_password : $('[name^=vcseminar_password]', target_element).val()
        },
        success: function(data) {
            console.log("success");
            json_data = JSON.parse(data);
            vcubeseminar_domainlist_get(json_data['result']['id']);
            $('[name=sesskey]').val(json_data['result']['sesskey']);
        },
        error:function(){
            console.log("failure");
        }
    });
};

var vcubeseminar_domainlist_delete = function (target_element) {
    if(!confirm($('[name=domainlistdeleteconfirm]').val())) {
        return false;
    }

    $.ajax({
        url: '/mod/vcubeseminar/domain.php?mode=delete&sesskey=' + $('[name=sesskey]').val(),
        type: 'post',
        datatype:'jsonp',
        data: {
            id : $('[name^=id]', target_element).val()
        },
        success: function(data) {
            console.log("success");
            json_data = JSON.parse(data);
            $('.domainrow' + json_data['result']['id']).remove();
            $('[name=sesskey]').val(json_data['result']['sesskey']);
        },
        error:function(){
            console.log("failure");
        }
    });
};

var vcubeseminar_domainlist_validate = function(target_element) {
    console.log('VALIDATE');
    if($("[name^=vcseminar_domain]", target_element).val().length == 0) {
        $('input[type=submit]', target_element).attr('disabled', 'disabled');
        console.log("INVALID DOMAIN");
        return false;
    }
    if($('[name^=vcseminar_id]', target_element).val().length == 0) {
        $('input[type=submit]', target_element).attr('disabled', 'disabled');
        console.log("INVALID ID");
        return false;
    }
    if($('[name^=vcseminar_password]', target_element).val().length == 0) {
        console.log("INVALID PASS");
        $('input[type=submit]', target_element).attr('disabled', 'disabled');
        return false;
    }
    $('input[type=submit]', target_element).removeAttr('disabled');
    return true;
};