<?php session_start();
 
 // si la variable  de sesion <acumulador> NO existe, la inicializa en 0
 if (!isset($_SESSION['contador_intentos'])){
    $_SESSION['contador_intentos']=0;
 }

require 'admin/PHP/Database.php';

//primero que todo se valida que se haya hecho un post
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
//luego validamos que las variables run y password existan y venga con algun valor
if (isset($_POST["run"]) && !empty($_POST["run"]) &&!is_null($_POST["run"]) && isset($_POST["password"]) && !empty($_POST["password"])&&!is_null($_POST["password"])){
//desde aqui comienza a ejecutarse el programa solo si hay valores
		$errores = '';
//Convierte los caracteres del run y password aplicables a entidades HTML
 		$run = htmlentities($_POST['run'], ENT_NOQUOTES);
 		$password = htmlentities($_POST['password']);
 	//aplica el algoritmo de encritacion sha512 a las password de entrada
 		$password = hash('sha512', $password);
 	//var_dump($password);	
//conecta la base de datos
try {
 		$pdo = DATABASE::connect();
 	} catch (PDOException $e) {
 		echo "Error:" . $e->getMessage();
}

    //prepara la consulta, que busca algun registro en la tabla de los ususarios bloqueados que coincida con el run ingresado
 	$query_IPBLOCK = $pdo->prepare('select RUN from sl_ipblock where RUN = ? ');
	$query_IPBLOCK->execute(array($run));
	$IPBLOCK = $query_IPBLOCK->fetch(PDO::FETCH_ASSOC);
   
    if (!$IPBLOCK['RUN']){ // si no esta dentro los bloqueados deja seguir 
	
// prepara la consulta que retorna la pasword de la tabla usuarios, mientras coincida el rut ingresado, estè habilitado y su rol sea consultor
	$statement = $pdo->prepare("SELECT password FROM usuarios WHERE run = ?  AND habilitado=1 AND rol = 2");
 	$statement->execute(array($password));
 	$pasword_bd = $statement->fetch(PDO::FETCH_ASSOC);
    

   if (!empty($pasword_bd) && $pasword_bd==$pasword  ) {//si las      condiciones se cumplen y la clave es correcta deja entrar al sistema
     limpiar_login();
     header('Location:contenido.php');  
   
    }else if ($_SESSION['contador_intentos']>5) {//intento fallido
    //pregunta si ha pasdo los 5 intentos se inhabilita el usuario y se  insertan sus datos en la tabla de bloqueados 
    $block_usuario=$pdo->prepare("UPDATE usuarios SET habilitado = 2 WHERE run = ?");
			$block_usuario->execute(array($run));
	
	$insert_block=$pdo->prepare("insert into SL_IPBLOCK (IP,RUN) values (?,?)");
			$insert_block->execute(array($_SERVER['REMOTE_ADDR'],$run));
    $errores .='password invalida';

    }else{//si es un intento fallido pero no ha superado los 5 se incrementa el acumulador en 1
    //y se inserta un registro  en la tabla de intentos sl_acces
    $access_query=$pdo->prepare("insert into sl_access (IP, STATE, USER, PASS) values (?,?,?,?)");
 	$access_query->execute(array($_SERVER['REMOTE_ADDR'],0,$run,$password));

     $_SESSION['contador_intentos']++; // 5 se incrementa el acumulador en 1  
     $errores .='password invalida';
    }
 	
	} else {// si  esta dentro lo bloqueados no deja seguir  y manda un mensaje de errrores
	$errores .='Su cuenta se encuentra bloqueda';
 	}
}//termino de la condicion valores vacios o llenos

}//termino de la condicion POST
 limpiar_login(); // se limpia las cajas de texto
 Database::disconnect();// se desconecta la BD
 require 'views/login.view.php';// se llama la vista login


  function limpiar_login()//metodo publico que limpia las varibles run y password
 {
 	 unset($_POST['run'] );
 	 unset($_POST['password']);
 }

 ?>