<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="frontend/style.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css"
          integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Questrial&display=swap" rel="stylesheet">


    <title>Quizlet audio converter</title>
</head>
<body>

<h1 id="pageHeader">Quizlet Audio</h1>

<form action="">
    <input id="linkInput" class="input" type="text" placeholder="Lernset Link" required>
    <div class="silencesDiv">
        <div class="silenceDiv">
            <label for="shortSilenceInput">Pause zwischen Begriffe</label>
            <input id="shortSilenceInput" class="silenceInput input" type="number" placeholder="" required>
        </div>
        <div class="silenceDiv">
            <label for="longSilenceInput">Pause zwischen Wortpaar</label>
            <input id="longSilenceInput" class="silenceInput input" type="number" placeholder="" required>
        </div>
    </div>
    <div class="testFieldDiv">
        <label for="testInput" class="label">Test (nur 10 Karten)</label>
        <label>
            <input id="testInput" class="input" type="checkbox" placeholder="">
            <span></span>
        </label>
    </div>

    <button type="submit" id="submitBtn" class="input">Audio generieren</button>

</form>


<?php
ini_set('max_execution_time', '900'); ?>

<footer>
    <div class="navbar navbar-expand-sm navbar-light fixed-bottom bg-light site-footer">
        <div class="mx-auto footerContent">
            <p class="navbar-text">Made with
                <i class="fas fa-heart"></i> by Samuel Gfeller
            </p>
            <a href="https://github.com/samuelgfeller/quizlet-audio-converter" class="btn btn-sm btn-light"
               target="_blank">
                <i class="fab fa-github-alt"></i>
            </a>
        </div>
    </div>
</footer>
</body>
</html>

