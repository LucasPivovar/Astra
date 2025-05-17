<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunidade</title>
    <?php
        include('./components/header.php')
    ?>
    <link rel="stylesheet" href="./styles/community.css">
</head>
<body>
    <div class="newPost">
        <button id="btnUserIcon" class="userIcon"><img src="./assets/profile-icon.svg" alt="profileIcon" style="width: 20px; height: 27px" ></button>
        <button id="newPostButton" class="postButton">Compartilhe Sua Evolução!</button>
        <button id="addImageBtn" class="addImage"><img src="./assets/image-icon.png" alt="addImage"></button>
    </div>
    <div class="communityMenu">
        <input type="text" placeholder="Encontre apoio para seus problemas!" id="searchInput" class="searchPosts">
        <div class="myPosts">
            <p class="myPostsText">Meus Posts</p>
        </div>
        <div class=""savedPosts>
            <p class="savedPostsText"><img src="./assets/star-icon.png" alt="starIcon" class="starIcon">Posts Salvos</p>
        </div>
    </div>
    <section id="postModalBackground">
        <div id="newPostModal" class="postModal">
            <p>aaaaaaa</p>
            <button id="closeModalBtn" class="closeModalButton"><p>X</p></button>
        </div>
    </section>
</body>
<script src="./scripts/community.js"></script>
</html>