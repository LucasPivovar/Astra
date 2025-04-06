<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Incio</title>
  <?php
    include('./componentes/header.php')
  ?>
  <link rel="stylesheet" href="./style/index.css">
</head>
<body>
  <main>
    <!-- dados -->
    <section class = "dados"> 
      <a class = "blue bem-estar" href = "chatbot.php">Uma nova abordagem para o bem estar ➜</a>
      <h1 class="title"> <span class="diferent-blue"> Astra: </span> Encontre apoio <br> na sua jornada de superação</h1>
      <p class = "descrição">Uma comunidade digital que oferece suporte personalizado, ferramentas de acompanhamento e conexões significativas para ajudar você a superar seus desafios.</p>
      <div class = "buttons">
        <a class = "button a-btn-main">Começar agora</a>
        <a class = "saiba-mais" href = "/"> Saiba mais ➜</a>
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
      <p class = "descrição"> Nossa plataforma combina tecnologia avançada e suporte humano para criar uma experiência completa de recuperação e bem-estar </p>
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
      <div class = "card card-horizontais card-IA-assistente" id="redirecionament-IA">
        <h1 class = "title title-card">Assistente de IA Personalizados</h1>
        <p> Nosso assistente virtual inteligente está disponível 24 horas por dia para oferecer orientação, exercícios de respiração, técnicas de mindfulness e estratégias personalizadas para ajudar você a superar momentos difíceis. </p>
        <ul class = "ul">
          <li class = "verificado"> Suporte imediato em momentos de crise</li>
          <li class = "verificado"> Técnicas personalizadas baseadas no seu progresso</li>
          <li class = "verificado"> Recursos educativos sobre dependência</li>
          <li class = "verificado"> Lembretes motivacionais diários</li>
        </ul>
        <a class = "button a-btn-IA a-btn-main" href ="chatbot.php"> Experimente o assistente </a>
      </div>
    </section>
    <!-- Conheça Recursos -->
    <section class = "conheca-recursos">
      <h1 class = "diferent-blue title"> Conheça nossos recursos </h1>
      <p class = "descrição"> Ferramentas desenvolvidas para apoiar você em cada etapa da sua jornada de superação </p>
      <div class = "cards cards-recursos">
        <div class = "card card-recurso">
          <img src="./svgs/circulo.svg" alt="" class = "svg">
          <h1 class = "title-card "> Acompanhamento de Metas </h1>
          <p> Defina objetivos, acompanhe seu progresso e celebre suas conquistas </p>
          <a href="/" class = "button a-btn-recurso"> Acessar metas ➜ </a>
        </div>
        <div class = "card card-recurso">
          <img src="./svgs/robot.svg" alt="" class = "svg">
          <h1 class = "title-card"> Assistente Virtual </h1>
          <p> Receba apoio e orientação personalizada 24 horas por dia </p>
          <a href="chatbot.php" class = "button a-btn-recurso "> Falar com assistente ➜ </a>
        </div>
        <div class = "card card-recurso">
          <img src="./svgs/circulo.svg" alt="" class = "svg">
          <h1 class = "title-card"> Comunidade </h1>
          <p> Conecte-se com outros membros, compartilhe experiências e histórias </p>
          <a href="/" class = "button a-btn-recurso"> Ver Comiunidade ➜ </a>
        </div>
      </div>
    </section>
  </main>
</body>
</html>