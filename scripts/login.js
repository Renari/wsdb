function checkUsername(){
  if (validUsername()){
    $.ajax({
      method: "POST",
      url: "/api/username",
      data: {username: $('#username').val()}
    }).done(function(data){
      if (data == 'true'){
        usernameError(false);
      }
      else{
        usernameError(true);
      }
  });
  }
  else{
    usernameError(true);
  }
}
function usernameError(error){
  if (error == true){
    $('#usernamegroup').removeClass('has-success').addClass('has-error');
    $('#usernamefeedback').removeClass('glyphicon-ok').addClass('glyphicon-remove');
  }
  else{
    $('#usernamegroup').removeClass('has-error').addClass('has-success');
    $('#usernamefeedback').removeClass('glyphicon-remove').addClass('glyphicon-ok');
  }
}
function validUsername(){
    var patt = new RegExp('^[a-zA-Z]{3,15}$');
    if (patt.test($('#username').val())){
      return true;
    }
    else{
      return false;
    }
}
$('#login').click(function(){
  $('#loginmodal').modal('show');
});
$('#register').submit(function(event){
  if (!validUsername()){
    return false;
  }
  return true;
});
if ($('#register').length){
  $('#register').modal({
    backdrop: 'static',
    keyboard: false
  });
  $('#username').keyup(function(){
    checkUsername();
  });
  if ($('#username').val()){
    checkUsername();
  }
}
