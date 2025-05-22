<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Incio</title>
  <link rel="stylesheet" href="./styles/avaliacoes.css">
  <link rel="shortcut icon" type="imagex/png" href="./assets/logo.svg">

</head>
<body>
  <main>
    <!-- Não alterar a ordem, sujeito a conflito de estilos. -->
    <?php
      include('./components/header.php')
    ?>
    <link rel="stylesheet" href="./styles/index.css">
    
    <!-- dados -->
    <section class = "dados">
      <div class = "descrita">
        <h1 class="title title-dados"> <span class="purple"> Astra </span> <br> Encontre apoio  na sua jornada de superação</h1>
        <p class = "descricao">Uma comunidade digital que oferece suporte personalizado, ferramentas de acompanhamento e conexões significativas para ajudar você a superar seus desafios.</p>
        <div class = "buttons">
          <button id = "cmaButton" class = "button a-btn-main">Começar agora</button>
          <button onclick="rolar('projeto')" class = "saiba-mais" href = "/"> Saiba mais</button>
        </div>
      </div>
      <img src="./assets/ilstr.svg" alt="Ilustração" class="ilstr">
    </section>

    <!-- Projeto -->
    <section id="projeto" class = "projeto">
      <h1 class = "purple title title-projeto"> Como a Astra pode ajudar você </h1>
      <p class = "descricao descricao-plataforma"> Nossa plataforma combina tecnologia avançada e suporte humano para criar uma experiência completa de recuperação e bem-estar </p>
      <div class = "grid-card">
        <div class = "card card-IA cards-horizontais">
          <img src="./assets/Robot.svg" alt="Figura Robô" class = "svg">
            <h1 class = "title-card"> Assistente Virtual Inteligente
            </h1>
            <p class = "IA"> Nossa IA personaliza o suporte com base nas suas necessidades, oferecendo técnicas práticas e orientações em momentos de crise. </p>
        </div>
        <div class = "card cards-horizontais card-meta">
          <img src="./assets/Circulo.svg" alt="" class = "svg">
            <h1 class = "title-card"> Sistema de Metas e Progresso </h1>
            <p> Estabeleça objetivos personalizados, acompanhe seu progresso e visualize sua evolução através de gráficos interativos.
            </p>
        </div>
        <div class = "card cards-horizontais card-comunidade">
            <img src="./assets/People.svg" alt="" class = "svg">
            <h1 class = "title-card"> Comunidade de Apoio </h1>
            <p> Conecte-se com pessoas que enfrentam desafios semelhantes, compartilhe experiências e encontre inspiração em histórias de superação.</p>
        </div>
        <div class = "card cards-horizontais card-vantagens">
            <img src="./assets/Medalha.svg" alt="" class = "svg">
            <h1 class = "title-card"> Reconhecimento e Recompensas </h1>
            <p> Conecte-se com pessoas que enfrentam desafios semelhantes, compartilhe experiências e encontre inspiração em histórias de superação. </p>
        </div>
        <div class = "card cards-horizontais card-IA-assistente" id="redirecionament-IA">
          <h1 class = "title title-card">Assistente de IA Personalizados</h1>
          <p> Nosso assistente virtual inteligente está disponível 24 horas por dia para oferecer orientação, exercícios de respiração, técnicas de mindfulness e estratégias personalizadas para ajudar você a superar momentos difíceis. </p>
          <ul class = "ul">
            <li class = "verificado"> <img src="./assets/Verificado.svg" alt=""> Suporte imediato em momentos de crise</li>
            <li class = "verificado"> <img src="./assets/Verificado.svg" alt="">Técnicas personalizadas baseadas no seu progresso</li>
            <li class = "verificado"> <img src="./assets/Verificado.svg" alt="">Recursos educativos sobre dependência</li>
            <li class = "verificado"> <img src="./assets/Verificado.svg" alt="">Lembretes motivacionais diários</li>
          </ul>
          <a class = "button a-btn-IA a-btn-main" href ="bot.php"> Experimente o assistente </a>
        </div>
      </div>
    </section>

    <!-- Avaliações -->

    <?php include('./components/avaliacoes.php'); ?>

    <script src="./scripts/avaliacoes.js"></script>
    <script>
      // Pintar o elemento do nav em que o usuário está presente 
      const colorMenu = document.querySelectorAll('.btn-menu li a')
      colorMenu[0].classList.add('purple', 'lineA-ativo')
      colorMenu[0].classList.remove('lineA')
      
      // Rolagem do Saiba mais
      function rolar(id) {
        document.getElementById(id).scrollIntoView({
          behavior: 'smooth'
        });
      }</script>
  </main>
</body>
</html>