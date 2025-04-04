<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <?php
    include('./componentes/header.php')
  ?>
  <link rel="stylesheet" href="./style/index.css">
</head>
<body>
  <main>
    <!-- dados -->
    <section class = "dados"> 
      <div class ="link-bem-estar">
      <p class = "span bem-estar">Uma nova abordagem para o bem estar</p>
      </div>
      <h1 class="title"> <span class="diferent-blue"> Astra: </span> Encontre apoio <br> na sua jornada de superação</h1>
      <h2 class = "descrição">Uma comunidade digital que oferece suporte personalizado, ferramentas de acompanhamento e conexões significativas para ajudar você a superar seus desafios.</h2>
      <div class = "buttons">
        <button class = "button btn-main">Começar agora</button>
        <p class = "saiba-mais"> Saiba mais</p>
      </div>
      <div class = "cards">
        <div class = "card">
          <h1 class = "title-card"> 2,500+ </h1>
          <p> Usuários ativos </p>
        </div>
        <div class = "card">
          <h1 class = "title-card"> 87% </h1>
          <p> Alcançaram metas </p>
        </div>
        <div class = "card">
          <h1 class = "title-card"> 24/7 </h1>
          <p> Suporte da IA </p>
        </div>
      </div>
    </section>
    <!-- Projeto -->
    <section class = "projeto">
      <h1 class = "diferent-blue title"> Como a Astra pode ajudar você </h1>
      <h2 class = "descrição"> Nossa plataforma combina tecnologia avançada e suporte humano para criar uma experiência completa de recuperação e bem-estar </h2>
      <div class = "cards grid-card">
        <div class = "card card-IA cards-horizontais">
          <img src="./svgs/robot.svg" alt="Figura Robô" class = "svg">
            <h1 class = "title-card"> Assistente Virtual Inteligente
            </h1>
            <p class = "IA"> Nossa IA personaliza o suporte com base nas suas necessidades, oferecendo técnicas práticas e orientações em momentos de crise. </p>
        </div>
        <div class = "card cards-horizontais card-meta">
          <img src="./svgs/circulo.svg" alt="" class = "svg">
            <h1 class = "title-card"> Sistema de Metas e Progresso </h1>
            <p> Estabeleça objetivos personalizados, acompanhe seu progresso e visualize sua evolução através de gráficos interativos.
            </p>
        </div>
        <div class = "card cards-horizontais card-comunidade">
            <img src="./svgs/people.svg" alt="" class = "svg">
            <h1 class = "title-card"> Comunidade de Apoio </h1>
            <p> Conecte-se com pessoas que enfrentam desafios semelhantes, compartilhe experiências e encontre inspiração em histórias de superação.</p>
        </div>
        <div class = "card cards-horizontais card-vantagens">
            <img src="./svgs/medalha.svg" alt="" class = "svg">
            <h1 class = "title-card"> Reconhecimento e Recompensas </h1>
            <p> Conecte-se com pessoas que enfrentam desafios semelhantes, compartilhe experiências e encontre inspiração em histórias de superação. </p>
        </div>
      </div>
      <div class = "card card-horizontais card-IA-assistente">
        <h1 class = "title title-card">Assistente de IA Personalizados</h1>
        <p> Nosso assistente virtual inteligente está disponível 24 horas por dia para oferecer orientação, exercícios de respiração, técnicas de mindfulness e estratégias personalizadas para ajudar você a superar momentos difíceis. </p>
        <ul class = "ul">
          <li class = "verificado"> Suporte imediato em momentos de crise</li>
          <li class = "verificado"> Técnicas personalizadas baseadas no seu progresso</li>
          <li class = "verificado"> Recursos educativos sobre dependência</li>
          <li class = "verificado"> Lembretes motivacionais diários</li>
        </ul>
        <button class = "button btn-IA btn-main"> Experimente o assistente </button>
      </div>
    </section>
  </main>
</body>
</html>