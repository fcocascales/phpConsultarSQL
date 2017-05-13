<?php
	require_once "Consulta.php";
	$c = new Consulta();

?><!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<title>Consultar</title>
	<link rel="stylesheet" href="estilos.css">
</head>
<body>
	<main class="table">
		<section>
			<article id="input">
				<form action="" method="post">
					<h1>Consultar</h1>
					<aside id="flotante">
						<fieldset>
							<legend>Conexión</legend>
							<p>
								<label for="conexion">Cadena de conexión:</label>
								<input type="text" name="conexion" id="conexion" value="<?php echo $c->getConexion() ?>" spellcheck="false">
							</p>
							<p>
								<label for="usuario">Usuario:</label>
								<input type="text" name="usuario" id="usuario" value="<?php echo $c->getUsuario() ?>" spellcheck="false">
							</p>
							<p>
								<label for="clave">Contraseña:</label>
								<input type="text" name="clave" id="clave" value="<?php echo $c->getClave() ?>" spellcheck="false">
							</p>
						</fieldset>
						<fieldset>
							<legend>Opciones</legend>
							<p>
								<label for="conexion">Máximo número de filas:</label>
								<input type="text" name="limite" id="limite" value="<?php echo $c->getLimite() ?>">
							</p>
						</fieldset>
					</aside>
					<fieldset id="container">
						<legend>SQL</legend>
						<textarea name="sql" autofocus="on" spellcheck="false"><?php echo $c->getSQL() ?></textarea>
						<p>
							<button>Enviar</button>
						</p>
					</fieldset>
				</form>
			</article>
			<article id="output">
				<fieldset>
					<legend>Resultado</legend>
					<?php $c->print() ?>
				</fieldset>
			</article>
		</section>
	</main>
</body>
</html>
