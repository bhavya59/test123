$(".activate-user").on("click", function () {
    if (window.confirm("do you want to activate?")) {
        var userid = this.id;
        //   alert(userid);

        var userarr = userid.split('_');
        var formdata = {userid: userarr[1]};
        // var url = "http://localhost/TravelAgentsDev/Home/activateuser/" + userarr[1];
        var url = base_url + "Home/activateuser/" + userarr[1];
        $.ajax({
            'url': url,
            data: {userid: userarr[1]},
            success: function (resp) {
                // alert(resp);
                //var root = "http://172.17.15.230/TravelAgentsDev/Home/activateuser/";
//                                return false;

                if (resp == 1) {
//                                    var encode =  JSON.parse("msg=1&froml=3");
                    // window.location.href = root + "1/3";
                    //  window.location.href = "http://localhost/TravelAgentsDev/Home/user_authorization/3";
                    window.location.href = base_url + "Home/user_authorization/3";
                } else {
                    alert("problem occured while Activating TravelAgent");
                }
            }
        });
    } else {
        return false;
    }
});



//deactivat user
$(".deactivate-user").on("click", function () {
    if (window.confirm("do you want to deactivate?")) {
        var userid = this.id;
        var userarr = userid.split('_');
        var formdata = {userid: userarr[1]};
//		var url 	= base_url+"/Home/activateuser";
        //var url = "http://localhost/TravelAgentsDev/Home/deactivateuser/" + userarr[1];
        var url = base_url + "Home/deactivateuser/" + userarr[1];
//              console.log(url);
//              return false;
        $.ajax({
            'url': url,
            data: {userid: userarr[1]},
            //'dateType': 'text',
            success: function (resp) {
                // alert(resp);
                if (resp == 1) {
//                                    var encode =  JSON.parse("msg=1&froml=3");
                    // window.location.href = root + "1/3";
                    //window.location.href = "http://localhost/TravelAgentsDev/Home/user_authorization/2";
                    window.location.href = base_url + "Home/user_authorization/2";
                } else {
                    alert("problem occured while Deactivating TravelAgent");
                }
            }
        });
    } else {
        return false;
    }
});


$(document).ready(function () {
    //var today = new Date();
    var date_input = $('#datepicker'); //our date input has the name "date"
    var container = $('.bootstrap-iso form').length > 0 ? $('.bootstrap-iso form').parent() : "body";
    $('#datepicker').datepicker({
        endDate: '+0d',
        format: 'yyyy-mm-dd',
        container: container,
        todayHighlight: true,
        autoclose: true,
    });

})

$(document).ready(function () {
    //var today = new Date();
    var date_input = $('.mydatepicker'); //our date input has the name "date"
    var container = $('.bootstrap-iso form').length > 0 ? $('.bootstrap-iso form').parent() : "body";
    $('.mydatepicker').datepicker({
        startDate: new Date(),
        format: 'yyyy-mm-dd',
        container: container,
        todayHighlight: true,
        autoclose: true,
    });
})






function get_states() {
    //   alert("getting states");
    $.ajax({
        //url: "http://localhost/TravelAgentsDev/Home/get_states",
        url: base_url + "Home/get_states",
        //url : "<?php echo base_url(Home/update_user/')?>/" +id,
        type: "GET",
        dataType: "JSON",
        success: function (data)
        {
            $('#state').html(data);
        },
        error: function (jqXHR, textStatus, errorThrown)
        {
            alert('Error getting data ');
        }
    });


}



function getdistricts(val) {
    //alert("get districts");
    //alert(val);
    var val = val.split("~");
    //alert(val[0]);
    $.ajax({
        //url: "base_url"+/Home/get_districts",
        //url: "http://localhost/TravelAgentsDev/index.php/Home/get_districts",
        url: base_url + "index.php/Home/get_districts",
        data: {state_id: val[0]},
        //method:"POST",
        dataType: 'JSON',
        success: function (data) {
            var my_data = "<option>Select District</option>";
            $.each(data, function (i, data) {
                my_data += "<option value='" + data.district_id + "~" + data.district_name + "'>" + data.district_name + "</option>"
            });
            $('#district').html(my_data);
            $('#user_district').html(my_data);
        }
    });
}

function logoutmenu() {
    jQuery('.top_cart').click(function (e) {
        e.preventDefault();
        var target = jQuery(".top_cart_con");

        if (target.is(':visible'))
            jQuery('.top_cart_con').css('cssText', 'display: none !important');

        else
            jQuery('.top_cart_con').css('cssText', 'display: block !important');
    });
}

function checkfirstname(f) {
    // debugger;
    var s = " !@#$%^&*()+=[]\\\';,./{}|\":<>?";
    str = f.value;
    for (var i = 0; i < str.length; i++)
    {
        if (s.indexOf(str.charAt(i)) != -1)
        {
            data = "special chars and spaces not allowed";
            $("#firstnamevalidation").html(data);
            document.getElementById('first_name').value = "";
            f.value = '';
            f.focus();
            return false;
        } else {
            //data="";
            $("#firstnamevalidation").html("");
        }
    }
}



function checkusername(f) {
   // alert(f.value);
    // debugger;
    var s = "!@#$%^&*()+=-[]\\\';,./{}|\":<>?";
    str = f.value;
    //alert(str);
    for (var i = 0; i < str.length; i++)
    {
        if (s.indexOf(str.charAt(i)) != -1)
        {
            alert(s.indexOf(str.charAt(i)));
            data = "special chars and spaces not allowed";
            $("#user-availability-status").html(data);
            document.getElementById('user_name').value = "";
            f.value = '';
            
            f.focus();
            return false;
        }
    }
    jQuery.ajax({
        url: base_url + "/home/check_username",
        data: 'user_name=' + $("#user_name").val(),
        type: "POST",
        success: function (data) {
            $("#user-availability-status").html(data);
              //alert(data);
            if (data.trim() == "") {
                $("#btn-submit").attr("disabled", false);
            } else {
                $("#btn-submit").attr("disabled", true);
//                alert("empty");
                f.value = '';
                f.focus();
                return false;
            }
        },
        error: function () {
        }
    });
}

function validate_name(f){
    //alert(f);
    // debugger;
    var s = " !@#$%^&*()+=-[]\\\';,./{}|\":<>?";
    str = f.value;
    //alert(str);
    for (var i = 0; i < str.length; i++)
    {
        if (s.indexOf(str.charAt(i)) != -1)
        {
            data = "special chars and spaces not allowed";
            $("#validate_username").html(data);
            //document.getElementById('validate_username').value = "";
            f.value = '';
            f.focus();
            return false;
        }else{
             $("#validate_username").html("");
        }
    }
}
function validate_lastname(f){
    //alert(f);
    // debugger;
    var s = " !@#$%^&*()+=-[]\\\';,./{}|\":<>?";
    str = f.value;
    //alert(str);
     for (var i = 0; i < str.length; i++)
    {
        if (s.indexOf(str.charAt(i)) != -1)
        {
            data = "special chars and spaces not allowed";
            $("#validate_lastname").html(data);
            f.value = '';
            f.focus();
            return false;
        }else{
             $("#validate_lastname").html("");
        }
    }
}
function validate_email(f){
    //alert(f);
    // debugger;
    var s = " !#$%^&*()+=-[]\\\';,/{}|\":<>?";
    str = f.value;
    //alert(str);
     for (var i = 0; i < str.length; i++)
    {
        if (s.indexOf(str.charAt(i)) != -1)
        {
            data = "special chars and spaces not allowed";
            $("#validate_email").html(data);
            f.value = '';
            f.focus();
            return false;
        }else{
             $("#validate_email").html("");
        }
    }
}

function validate_phone(f) {
    var Obj = document.getElementById("user_phone_number").value;
    if (Obj.length == 10) {

        //alert();
        // debugger;
        var s = " !@#$%^&*()+=[]\\\';,./{}|\":<>?abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        str = f.value;
        for (var i = 0; i < str.length; i++)
        {
            if (s.indexOf(str.charAt(i)) != -1)
            {
                data = "special chars and spaces not allowed";
                $("#mobileno-availability-status").html(data);
                //document.getElementById('mobile_number').value = "";
                f.value = '';
                f.focus();
                return false;
            }else{
                 $("#validate_phone").html("");
            }
        }
    } else {
        $("#validate_phone").html("please enter 10 digit mobile number");
        f.value = '';
        f.focus();
        return false;
    }
}

function validate_id_proof(f){
    //alert(f);
    // debugger;
    var s = "@!#$%^&*()+=-[]\\\';,./{}|\":<>?1234567890";
    str = f.value;
    //alert(str);
     for (var i = 0; i < str.length; i++)
    {
        if (s.indexOf(str.charAt(i)) != -1)
        {
            data = "special chars,numbers and spaces not allowed";
            $("#validate_id_proof").html(data);
            f.value = '';
            f.focus();
            return false;
        }else{
             $("#validate_id_proof").html("");
        }
    }
}

function validate_id_proof_number(f){
    //alert(f);
    // debugger;
    var s = " @!#$%^&*()+=-[]\\\';,./{}|\":<>?";
    str = f.value;
    //alert(str);
     for (var i = 0; i < str.length; i++)
    {
        if (s.indexOf(str.charAt(i)) != -1)
        {
            data = "special chars and spaces not allowed";
            $("#validate_id_proof_number").html(data);
            f.value = '';
            f.focus();
            return false;
        }else{
             $("#validate_id_proof_number").html("");
        }
    }
}
function checkpassword(f) {
    //alert();
    $('#password, #confirm_password').on('keyup', function () {
        if ($('#password').val() == $('#confirm_password').val()) {
            $('#message').html('Matching').css('color', 'green');
            //  $("#btn-submit").attr("disabled", false);
        } else
            $('#message').html('Not Matching').css('color', 'red');
        // $("#btn-submit").attr("disabled", true);
        //f.value = '';

    });

}



/*function checkusername(f)
 {
 debugger;
 var s = "!@#$%^&*()+=-[]\\\';,./{}|\":<>?";
 //str=document.getElementById('<%=TextBox1.ClientID%>').value;
 str=f.value;
 //alert(str);
 for (var i = 0; i < str.length; i++)
 {
 if (s.indexOf(str.charAt(i)) != -1)
 {
 data="special chars not allowed";
 $("#user-availability-status").html(data);
 //alert ("special characters are not allowed.\n");
 document.getElementById('user_name').value="";
 return false;  
 } 
 }
 jQuery.ajax({
 url: base_url+"/Home/check_username",
 data: 'user_name=' + $("#user_name").val(),
 type: "POST",
 success: function (data) {
 $("#user-availability-status").html(data);
 //  alert(data);
 if (data == "") {
 $("#btn-submit").attr("disabled", false);
 } else {
 $("#btn-submit").attr("disabled", true);
 }
 },
 error: function () {
 }
 });
 }*/


/*function checkusername() {
 // !(/^[A-z209;241;0-9]*$/i).test(f.value)?f.value = f.value.replace(/[^A-z209;241;0-9]/ig,''):null;
 //!(/^[A-z209;241;0-9]*$/i).test(f.value)?f.value = f.value.replace(/[^A-z209;241;0-9]/ig,''):null;
 jQuery.ajax({
 url: base_url+"/Home/check_username",
 data: 'user_name=' + $("#user_name").val(),
 type: "POST",
 success: function (data) {
 $("#user-availability-status").html(data);
 //  alert(data);
 if (data == "") {
 $("#btn-submit").attr("disabled", false);
 } else {
 $("#btn-submit").attr("disabled", true);
 }
 },
 error: function () {
 }
 });
 
 }*/

/*function validatecpassword(f){
 // debugger;
 var s = "!@#$%^&*()+=[]\\\';,./{}|\":<>?";
 str=f.value;
 //alert(str);
 for (var i = 0; i < str.length; i++)
 {
 if (s.indexOf(str.charAt(i)) != -1)
 {
 data="special chars and spaces not allowed";
 $("#validatedesignation").html(data);
 document.getElementById('designation').value="";
 f.value = '';
 f.focus();
 return false; 
 } else{
 var data="";
 $("#validatedesignation").html(data);
 }  
 }   
 }*/
function checkdesignation(f) {
    // debugger;
    var s = "!@#$%^&*()+=[]\\\';,./{}|\":<>?";
    str = f.value;
    //alert(str);
    for (var i = 0; i < str.length; i++)
    {
        if (s.indexOf(str.charAt(i)) != -1)
        {
            data = "special chars and spaces not allowed";
            $("#validatedesignation").html(data);
            document.getElementById('designation').value = "";
            f.value = '';
            f.focus();
            return false;
        } else {
            var data = "";
            $("#validatedesignation").html(data);
        }
    }
}

function checkeusernameedit(f)
{
    //  debugger;
    var s = " !@#$%^&*()+=-[]\\\';,./{}|\":<>?";
    str = f.value;
    //alert(str);
    for (var i = 0; i < str.length; i++)
    {
        if (s.indexOf(str.charAt(i)) != -1)
        {
            data = "special chars and spaces not allowed";
            $("#useredit-availability-status").html(data);
            document.getElementById('user_name').value = "";
            return false;
        }
    }
    jQuery.ajax({
        url: base_url + "/index.php/Home/check_usernameedit",
        //data: 'user_name=' + $("#user_name").val() +'&edit_user_id'+ edit_user_id,
        data: 'edit_user_name=' + $("#user_name").val() + '&edit_user_id=' + $("#user_id").val(),
        type: "POST",
        success: function (data) {
            //alert(data);
            $("#useredit-availability-status").html(data);
            //alert(data);
            if (data == "") {
                $("#btn-submit").attr("disabled", false);
            } else {
                $("#btn-submit").attr("disabled", true);
                f.value = '';
                f.focus();
                return false;
            }

        },
        error: function () {
        }
    });
}


function checkbankname(f) {
//alert();
    var s = "!@#$%^&*()+=[]\\\';,./{}|\":<>?";
    var str = f.value;
    //alert(str);
    for (var i = 0; i < str.length; i++)
    {
        if (s.indexOf(str.charAt(i)) != -1)
        {
            var data = "special chars and spaces not allowed";
            $("#bankname-validation").html(data);
            document.getElementById('bank_name').value = "";
            f.value = '';
            f.focus();
            return false;
        } else {
            var data = "";
            $("#bankname-validation").html(data);
        }
    }
}

function validatepannumber(f) {
//alert();
    var Obj = document.getElementById("pan_number").value;
    if (Obj.length == 10) {
        var s = " -_!@#$%^&*()+=[]\\\';,./{}|\":<>?abcdefghijklmnopqrstuvwxyz";
        var str = f.value;
        //alert(str);
        for (var i = 0; (i < str.length); i++)
        {
            if ((s.indexOf(str.charAt(i)) != -1))
            {
                var data = "special chars and spaces not allowed";
                $("#pannumbervalidation").html(data);
                document.getElementById('pan_number').value = "";
                f.value = '';
                f.focus();
                return false;
            } else {
                var data = "";
                $("#pannumbervalidation").html(data);
            }
        }
    } else {
        $("#pannumbervalidation").html("please enter 10 digit pan number");
        f.value = '';
        f.focus();
        return false;
    }
}
function checkaccountno(f) {
    var s = " !@#$%^&*()+=[]\\\';,./{}|\":<>?abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    var str = f.value;
    //alert(str);

    for (var i = 0; i < str.length; i++)
    {

        if (s.indexOf(str.charAt(i)) != -1)
        {
            var data = "special chars and spaces not allowed";
            $("#accountno-validation").html(data);
            document.getElementById('account').value = "";
            f.value = '';
            f.focus();
            return false;
        } else {
            var data = "";
            $("#accountno-validation").html(data);
        }
    }

    jQuery.ajax({
        url: base_url + "/index.php/Home/check_accountno",
        data: 'account=' + $("#account").val(),
        type: "POST",
        success: function (data) {

            //alert(data);//here we have removed validation for account number avaiilability
            $("#accountno-availability-status").html(data);
            if (data == "") {
                $("#btn-submit").attr("disabled", false);
            } else {
                $("#btn-submit").attr("disabled", true);
                f.value = '';
                f.focus();
                return false;
            }
        },
        error: function () {
        }
    });
}

function checkaccountnoedit(f) {
    var s = " !@#$%^&*()+=[]\\\';,./{}|\":<>?abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    var str = f.value;
    //alert(str.length);
    if (str.length >= 10) {
        //alert();
        for (var i = 0; i < str.length; i++)
        {
            if (s.indexOf(str.charAt(i)) != -1)
            {
                var data = "special chars and spaces not allowed";
                $("#accountnoedit-validation").html(data);
                document.getElementById('account').value = "";
                f.value = '';
                f.focus();
                return false;
            } else {
                var data = "";
                $("#accountnoedit-validation").html(data);
            }
        }
        //alert("function");
        jQuery.ajax({
            url: base_url + "/index.php/Home/check_accountnoedit",
            data: 'edit_account=' + $("#account").val() + '&edit_user_id=' + $("#user_id").val(),
            type: "POST",
            success: function (data) {
                //alert(data);//here we have removed validation for account number avaiilability
                $("#accountnoedit-availability-status").html(data);
                if (data == "") {
                    $("#btn-submit").attr("disabled", false);
                } else {
                    $("#btn-submit").attr("disabled", true);
                    f.value = '';
                    f.focus();
                    return false;
                }
            },
            error: function () {
            }
        });
    } else {
        var data = "enter valid account number";
        $("#accountnoedit-validation").html(data);
        //f.value = '';


    }
}
function checkmobileno(f) {
    var Obj = document.getElementById("mobile_number").value;
    if (Obj.length == 10) {

        //alert();
        // debugger;
        var s = " !@#$%^&*()+=[]\\\';,./{}|\":<>?abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        str = f.value;
        for (var i = 0; i < str.length; i++)
        {
            if (s.indexOf(str.charAt(i)) != -1)
            {
                data = "special chars and spaces not allowed";
                $("#mobileno-availability-status").html(data);
                document.getElementById('mobile_number').value = "";
                f.value = '';
                f.focus();
                return false;
            }
        }
        jQuery.ajax({
            url: base_url + "/index.php/Home/check_mobileno",
            data: 'mobile_number=' + $("#mobile_number").val(),
            type: "POST",
            success: function (data) {
                $("#mobileno-availability-status").html(data);
                if (data == "") {
                    $("#btn-submit").attr("disabled", false);
                } else {
                    $("#btn-submit").attr("disabled", true);
                    f.value = '';
                    f.focus();
                    return false;
                }
            },
            error: function () {
            }
        });
    } else {
        $("#mobileno-availability-status").html("please enter 10 digit mobile number");
        f.value = '';
        f.focus();
        return false;
    }
}


function checkmobilenoedit(f) {
    //alert();
    var Obj = document.getElementById("mobile_number").value;
    if (Obj.length == 10) {
        $("#mobilenoedit-availability-status").html("");
        //alert();
        // debugger;
        var s = " !@#$%^&*()+=[]\\\';,./{}|\":<>?abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        str = f.value;
        for (var i = 0; i < str.length; i++)
        {
            if (s.indexOf(str.charAt(i)) != -1)
            {
                data = "special chars and spaces not allowed";
                $("#mobilenoedit-availability-status").html(data);
                document.getElementById('mobile_number').value = "";
                f.value = '';
                f.focus();
                return false;
            }
        }
        //alert
        jQuery.ajax({
            url: base_url + "/index.php/Home/check_mobilenoedit",
            data: 'edit_mobile_number=' + $("#mobile_number").val() + '&edit_user_id=' + $("#user_id").val(),
            type: "POST",
            success: function (data) {
                $("#mobilenoedit-availability-status").html(data);
                if (data == "") {
                    $("#btn-submit").attr("disabled", false);
                } else {
                    $("#btn-submit").attr("disabled", true);
                    f.value = '';
                    f.focus();
                    return false;
                }
            },
            error: function () {
            }
        });
    } else {
        $("#mobilenoedit-availability-status").html("please enter 10 digit mobile number");
        f.value = '';
        f.focus();
        return false;
    }
}
function checktelphno(f) {
    // alert();
    var Obj = document.getElementById("telephone_no").value;
    if ((Obj.length > 6) && (Obj.length < 12)) {
        // alert();
        //   debugger;
        var s = " !@#$%^&*()+=[]\\\';,./{}|\":<>?";
        str = f.value;
        for (var i = 0; i < str.length; i++)
        {
            if (s.indexOf(str.charAt(i)) != -1)
            {
                data = "special chars and spaces not allowed";
                $("#telephone_no-availability-status").html(data);
                document.getElementById('telephone_no').value = "";
                f.value = '';
                f.focus();
                return false;
            }
        }
        jQuery.ajax({
            url: base_url + "/index.php/Home/check_telphno",
            data: 'telephone_no=' + $("#telephone_no").val(),
            type: "POST",
            success: function (data) {
                $("#telephone_no-availability-status").html(data);
                if (data == "") {
                    $("#btn-submit").attr("disabled", false);
                } else {
                    $("#btn-submit").attr("disabled", true);
                    f.value = '';
                    f.focus();
                    return false;
                }
            },
            error: function () {
            }
        });
    } else {
        $("#telephone_no-availability-status").html("please enter valid number");
        f.value = '';
        f.focus();
        return false;
    }
}
function checktelphnoedit(f) {
    //alert()
    var Obj = document.getElementById("telephone_no").value;
    if ((Obj.length > 6) && (Obj.length < 12)) {
        // alert();
        //   debugger;
        var s = " !@#$%^&*()+=[]\\\';,./{}|\":<>?";
        str = f.value;
        for (var i = 0; i < str.length; i++)
        {
            if (s.indexOf(str.charAt(i)) != -1)
            {
                data = "special chars and spaces not allowed";
                $("#telephone_noedit-availability-status").html(data);
                document.getElementById('telephone_no').value = "";
                f.value = '';
                f.focus();
                return false;
            }
        }
        jQuery.ajax({
            url: base_url + "/index.php/Home/check_telphnoedit",
            data: 'edit_telephone_no=' + $("#telephone_no").val() + '&edit_user_id=' + $("#user_id").val(),
            type: "POST",
            success: function (data) {
                $("#telephone_noedit-availability-status").html(data);
                if (data == "") {
                    $("#btn-submit").attr("disabled", false);
                } else {
                    $("#btn-submit").attr("disabled", true);
                    f.value = '';
                    f.focus();
                    return false;
                }
            },
            error: function () {
            }
        });
    } else {
        $("#telephone_noedit-availability-status").html("please enter valid number");
        f.value = '';
        f.focus();
        return false;
    }
}

function checkemailid(f) {
    var s = " !#$%^&*()+=[]\\\';,/{}|\":<>?";
    str = f.value;
    for (var i = 0; i < str.length; i++)
    {
        if (s.indexOf(str.charAt(i)) != -1)
        {
            data = "special chars except(@ .) and spaces not allowed";
            $("#email_id-availability-status").html(data);
            document.getElementById('email_id').value = "";
            f.value = '';
            f.focus();
            return false;
        }
    }
    jQuery.ajax({
        url: base_url + "/index.php/Home/check_emailid",
        data: 'email_id=' + $("#email_id").val(),
        type: "POST",
        success: function (data) {
            $("#email_id-availability-status").html(data);
            if (data == "") {
                $("#btn-submit").attr("disabled", false);
            } else {
                $("#btn-submit").attr("disabled", true);
                f.value = '';
                f.focus();
                return false;
            }
        },
        error: function () {
        }
    });
}
function checkemailidedit(f) {
    jQuery.ajax({
        url: base_url + "/index.php/Home/check_emailidedit",
        data: 'edit_email_id=' + $("#email_id").val() + '&edit_user_id=' + $("#user_id").val(),
        type: "POST",
        success: function (data) {
            $("#email_idedit-availability-status").html(data);
            if (data == "") {
                $("#btn-submit").attr("disabled", false);
            } else {
                $("#btn-submit").attr("disabled", true);
                f.value = '';
                f.focus();
                return false;
            }
        },
        error: function () {
        }
    });
}

function checkaltemailid(f) {
    var s = " !#$%^&*()+=[]\\\';,/{}|\":<>?";
    str = f.value;
    for (var i = 0; i < str.length; i++)
    {
        if (s.indexOf(str.charAt(i)) != -1)
        {
            data = "special chars except(@ .) and spaces not allowed";
            $("#alt_email_id-availability-status").html(data);
            document.getElementById('alternate_email').value = "";
            f.value = '';
            f.focus();
            return false;
        }
    }
    jQuery.ajax({
        url: base_url + "/index.php/Home/check_altemailid",
        data: 'alternate_email=' + $("#alternate_email").val(),
        type: "POST",
        success: function (data) {
            $("#alt_email_id-availability-status").html(data);
            if (data == "") {
                $("#btn-submit").attr("disabled", false);
            } else {
                $("#btn-submit").attr("disabled", true);
                f.value = '';
                f.focus();
                return false;
            }
        },
        error: function () {
        }
    });
}

function checkaltemailidedit(f) {
    jQuery.ajax({
        url: base_url + "/index.php/Home/check_altemailidedit",
        data: 'edit_alternate_email=' + $("#alternate_email").val() + '&edit_user_id=' + $("#user_id").val(),
        type: "POST",
        success: function (data) {
            $("#alt_email_idedit-availability-status").html(data);
            if (data == "") {
                $("#btn-submit").attr("disabled", false);
            } else {
                $("#btn-submit").attr("disabled", true);
                f.value = '';
                f.focus();
                return false;
            }
        },
        error: function () {
        }
    });
}

function validateagency(f) {

    var s = "!@#$%^&*()+=[]\\\';,./{}|\":<>?";
    var str = f.value;
    //alert(str);

    for (var i = 0; i < str.length; i++)
    {

        if (s.indexOf(str.charAt(i)) != -1)
        {
            //alert('matched'+s.indexOf(str.charAt(i)));
            //return false;
            var data = "special chars and spaces not allowed";
            $("#validateagency").html(data);
            document.getElementById('agency_name').value = "";
            f.value = '';
            f.focus();
            return false;
        } else {
            var data = "";
            $("#validateagency").html(data);
        }
    }
}
function validatecity(f) {
    var s = "!@#$%^&*()+=[]\\\';,./{}|\":<>?";
    var str = f.value;
    //alert(str);
    for (var i = 0; i < str.length; i++)
    {
        if (s.indexOf(str.charAt(i)) != -1)
        {
            data = "special chars and spaces not allowed";
            $("#validatecity").html(data);
            document.getElementById('city').value = "";
            f.value = '';
            f.focus();
            return false;
        } else {
            data = "";
            $("#validatecity").html(data);
        }
    }
}
function validateservices(f) {
    var s = "!@#$%^&*()+=[]\\\';./{}|\":<>?";
    var str = f.value;
    //alert(str);
    for (var i = 0; i < str.length; i++)
    {
        if (s.indexOf(str.charAt(i)) != -1)
        {
            data = "special chars and spaces not allowed";
            $("#validateservices").html(data);
            document.getElementById('services_offer').value = "";
            f.value = '';
            f.focus();
            return false;
        } else {
            data = "";
            $("#validateservices").html(data);
        }
    }
}
//
//function scrollAction(scroll_id) {
//    alert("hii");            
//    alert(scroll_id);
//          jQuery.ajax({
//        url: base_url+"/index.php/Home/delete_scroll",
//        //data: {scrollid: scroll_id},
//        //data: 'scroll_id='+scroll_id,
//        type: "POST",
//        success: function (data) {
//            alert(data);
//        },
//        error: function () {
//        }
//    });    
//}

$(".delete-scroll").on("click", function () {
    if (window.confirm("do you want to delete scroll?")) {
        var scrollid = this.id;
        //alert("hii");
        var scrollarr = scrollid.split('_');
        //alert(scrollarr[1]);
        var formdata = {scrollid: scrollarr[1]};
        var url = base_url + "Home/delete_scroll/" + scrollarr[1];
        $.ajax({
            'url': url,
            data: {scrollid: scrollarr[1]},
            //'dateType': 'text',
            success: function (resp) {
                // alert(resp);
                if (resp == 1) {
                    window.location.href = base_url + "Home/scrolldatapage";
                } else {
                    alert("problem occured while Deleting Scroll Information");
                }
            }
        });
    } else {
        return false;
    }
});


$(".delete-offer").on("click", function () {
    if (window.confirm("do you want to delete offer?")) {
        var offerid = this.id;
        // alert("hii");
        var offerarr = offerid.split('_');
        //alert(offerarr[1]);
        var formdata = {offerid: offerarr[1]};
        var url = base_url + "Home/delete_offer/" + offerarr[1];
        $.ajax({
            'url': url,
            data: {offerid: offerarr[1]},
            //'dateType': 'text',
            success: function (resp) {
                // alert(resp);
                if (resp == 1) {
                    window.location.href = base_url + "Home/offersdatapage";
                } else {
                    alert("problem occured while Deleting Offer Information");
                }
            }
        });
    } else {
        return false;
    }
});


function ValidateGST(f) {
    //alert(f);
    var gstcnt = 0;
    //alert(ValidatePAN2(PAN));
    var Obj = document.getElementById("gst").value;
    var Obj1 = document.getElementById("gst");
    if (Obj.length == 15) {
        var STCD = Obj.slice(0, 2);
        var PANCD = Obj.slice(2, 12);
        var ENN = Obj.slice(12, 13);
        var DF = Obj.slice(13, 14);
        var CK = Obj.slice(14, 15);
        if (!$.isNumeric(STCD))
            gstcnt++;
        if (ValidatePAN2(PANCD) == 0)
            gstcnt++;
        if (!$.isNumeric(ENN))
            gstcnt++;
        if (!DF.match(/[a-z]/i))
            gstcnt++;

        data = "";
        $("#validategst").html(data);
        //document.getElementById('gst').value = "";
        //f.value = '';
//            f.focus();
//            return false;
        // if (!$.isNumeric(CK))
        // gstcnt++;
        //alert(STCD+"-"+PANCD+"-"+ENN+"-"+DF+"-"+CK);                                                        
        if (gstcnt != 0) {
            //alert("Invalid GST NO");
            data = "Invalid GST NO";
            $("#validategst").html(data);
            document.getElementById('gst').value = "";
            f.value = '';
            f.focus();
            return false;
//                                                            Obj.focus();
//                                                        alert(obj);
            Obj1.value = '';
        }
        if (gstcnt == 0) {
//                                                            alert("GST CLEARED");
        }
    } else {
        //alert("Invalid GST NO");
        data = "Invalid GST NO";
        $("#validategst").html(data);
        document.getElementById('gst').value = "";
        f.value = '';
        f.focus();
        return false;

        Obj1.value = '';
    }
}




function ValidatePAN() {
    var Obj = document.getElementById("pan_number");
    if (Obj.value != "") {
        var ObjVal = Obj.value;
        var panPat = /^([a-zA-Z]{5})(\d{4})([a-zA-Z]{1})$/;
        if (ObjVal.search(panPat) == -1) {
            alert("INVALID PAN NO");
            Obj.value = '';
            Obj.focus();
            return false;
        }
    }
}

function ValidatePAN2(pancd) {
    var Obj = document.getElementById("pan_number");
    //alert(Obj.value);
    if ((pancd.length == 10) && (pancd == Obj.value)) {
        var ObjVal = pancd;
        var panPat = /^([a-zA-Z]{5})(\d{4})([a-zA-Z]{1})$/;
        if (ObjVal.search(panPat) == -1) {
            return 0;
        } else {
            return 1;
        }

    } else {
        return 0;
    }
}

function validate_count(f){
    //alert(f);
}

function getwallet(val) {
    // alert(val);
    //alert("hii");
    if (document.getElementById("wallet").checked) {
        //alert("checked")
        //document.getElementById('wallethidden').disabled = true;


        if (parseInt(totalprice) >= parseInt(walletamt)) {
            var balancepayment = totalprice - walletamt;
            var walletbalance = 0;
        } else {
            alert("total price is less than wallet amount");
            var balancepayment = 0;
            var walletbalance = walletamt - totalprice;
        }
        alert("wallet balance after deduction" + walletbalance);
        //alert("remaining balance payment after including " + balancepayment);
        $("#balance").html(balancepayment);
//      window.location.href = "review?myvariable=" + balancepayment;
        //alert(val);
    } else {
        var balancepayment = totalprice;
       // alert("unchecked")
        //alert("wallet amount" + walletamt);
        //alert("balance payment" + balancepayment);
        $("#balance").html(balancepayment);
    }


}

function validate_ticket(f){
 //  alert(f);
   var Obj = document.getElementById("ticket_id").value;
    if (Obj.length == 10) {

        //alert();
        // debugger;
        var s = " !@#$%^&*()+=[]\\\';,./{}|\":<>?abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        str = f.value;
        for (var i = 0; i < str.length; i++)
        {
            if (s.indexOf(str.charAt(i)) != -1)
            {
                data = "special chars and spaces not allowed";
                $("#validate_ticket").html(data);
                //document.getElementById('mobile_number').value = "";
                f.value = '';
                f.focus();
                return false;
            }else{
                 $("#validate_ticket").html("");
            }
        }
    } else {
        $("#validate_ticket").html("please enter valid number");
        f.value = '';
        f.focus();
        return false;
    }
}

$(function () {
    $("#datepicker1,#datepicker2,#datepicker3,#datepicker4").datepicker();
})