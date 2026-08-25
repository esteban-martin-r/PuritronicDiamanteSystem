function ValidaFormulario() {
  var valorUsuario = document.getElementById("txtusuario").value;
  var userError = document.getElementById("user-error");

  var valorClave = document.getElementById("txtpassword").value;
  var passError = document.getElementById("pass-error");

  document.getElementById("txtusuario").style.background = "#f9f9f9";
  document.getElementById("txtpassword").style.background = "#f9f9f9";
  userError.style.display = "none";
  passError.style.display = "none";

  if (
    valorUsuario == null ||
    valorUsuario.length == 0 ||
    /^\s+$/.test(valorUsuario)
  ) {
    userError.textContent = "Debes escribir el usuario";
    userError.style.display = "block";
    document.getElementById("txtusuario").style.background = "#fff0f0";
    document.getElementById("txtusuario").focus();
    return false;
  } else if (valorClave == null || valorClave.length == 0) {
    passError.textContent = "La contraseña debe contener solo números";
    passError.style.display = "block";
    document.getElementById("txtpassword").style.background = "#fff0f0";
    document.getElementById("txtpassword").focus();
    return false;
  }

  return true;
}
