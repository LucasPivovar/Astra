<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>header</title>
  <link rel="stylesheet" href="./style/header.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">

</head>
<body>
  <nav>
    <h1 class="blue title">Astra</h1>
    <ul class = "btn-menu">
      <li><a href="" class="lineA">Comunidade</a></li>
      <li><a href="" class="lineA">IA Assistente</a></li>
      <li><a href="" class="lineA">Metas</a></li>
      <li><a href="" class="lineA">Inicio</a></li>
    </ul>
    <button id="btnLogin" class="loginButton button">Entre no Astra</button>
    <section id="modal" class="modalContainer">
      <div class="backgroundModal">
        <h2 id="titleModal">Entre em Sua Conta</h2>
        <form id="formLogin">
          <label for="userLoginForm" class="label userLoginLabel"> Usuário ou Email</label>
          <input type="text,email" id="userLoginForm">
          <label for="passwordLoginForm" class="label"> Senha</label>
          <input type="password" id="passwordLoginForm">
          <button type="submit" id="btnModal1" class="formButton button">Fazer Login</button>
        </form>
        <form id="formSignUp">
          <label for="user" class="label userLabel"> Usuário</label>
          <input type="text" id="user">
          <label for="email" class="label"> Email</label>
          <input type="email" id="email">
          <label for="password" class="label"> Senha</label>
          <input type="password" id="password">
          <button type="submit" id="btnModal2" class="formButton button">Registrar</button>
        </form>
        <p id="textSignUp">Não tem uma conta? <span id="signUp"class="register blue">Registre se agora</span></p>
      </div>
    </section>
  </nav>
  <script src="./script/header.js"></script>
</body>
</html>