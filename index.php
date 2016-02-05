<!DOCTYPE html>

<html>
    <head>
        <title>Vizus kalkulačka</title>
        
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Formulář pro online kalkulátor"/>
        <meta name="keywords" content="Vizus, online kalkulačka, Assignment"/>
        <meta name="author" content="Josef Jakub Jestřáb"/>
        <meta name="Robots" content="none"/>
        
        <script type="text/javascript" src="js/core.js"></script>
    </head>
    <body>
        <div id = header>
            <h1 class = "nor">Vizus kalkulačka</h1><br />
            <?php 
                @session_start();
                if((isset($_SESSION["info"])) && (!empty($_SESSION["info"]))){ ?>                 
                    <div id = "info"><p><?php echo $_SESSION["info"]; ?></p></div>
                <?php } //endif 
                @session_destroy();
                @session_start();
                 if (!isset($_SESSION["csrf_token"])) {
                    $_SESSION["csrf_token"] = rand(1, 1e9);
                }
                
            ?>            
        </div>            
            <div class = "w100" id = "main">
                <div id = "content">
                    <form action="Calc.php" method="post">
                        <div>
                            <label for="formula">Příklad:</label> 
                            <input type="text" name="formula" pattern= "[0-9 \(\)\+\.\/\*\-\–\,\ˆ\^]+" autofocus="autofocus" >
                            <input type='hidden' name='csrf_token' value='<?php echo $_SESSION["csrf_token"]; ?>' />
                            <input type="reset" value="Vyčistit">
                            <input type="submit" value="Spocitat">                        
                        </div>
                    </form>
                </div>    
            </div>
            <p class = "p10">Vizus@copyright</p>
    </body>
</html>

