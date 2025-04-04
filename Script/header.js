const onOff = document.getElementById('modal');
const formLogin = document.getElementById('formLogin');
const formSignUp = document.getElementById('formSignUp')
const signUp = document.getElementById('signUp');
const btn = document.getElementById('btnLogin');
const textSignUp = document.getElementById('textSignUp')

btn.onclick = function(){
  onOff.style.display = "block";
  formLogin.style.display = "flex";
  textSignUp.style.display = "block";
}
signUp.onclick = function(){
  formLogin.style.display = "none";
  formSignUp.style.display = "flex";
  textSignUp.style.display = "none";
  document.getElementById('titleModal').innerHTML = "Faça seu Registro";
}
window.onclick = function(i){
  if (i.target == onOff){
    onOff.style.display = "none";
    formSignUp.style.display = "none";

  }
}