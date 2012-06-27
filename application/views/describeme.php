<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
<title>Describe Last.fm user's tastes</title>

	<style type="text/css">

	::selection{ background-color: #E13300; color: white; }
	::moz-selection{ background-color: #E13300; color: white; }
	::webkit-selection{ background-color: #E13300; color: white; }

	body {
		background-color: #fff;
		margin: 40px;
		font: 13px/20px normal Helvetica, Arial, sans-serif;
		color: #4F5155;
	}

	a {
		color: #003399;
		background-color: transparent;
		font-weight: normal;
	}

	h1 {
		color: #444;
		background-color: transparent;
		border-bottom: 1px solid #D0D0D0;
		font-size: 24px;
		font-weight: normal;
		margin: 0 0 14px 0;
		padding: 14px 15px 10px 15px;
	}

	code {
		font-family: Consolas, Monaco, Courier New, Courier, monospace;
		font-size: 12px;
		background-color: #f9f9f9;
		border: 1px solid #D0D0D0;
		color: #002166;
		display: block;
		margin: 14px 0 14px 0;
		padding: 12px 10px 12px 10px;
	}

	#body{
		margin: 0 15px 0 15px;
	}
	
	p.footer{
		text-align: right;
		font-size: 11px;
		border-top: 1px solid #D0D0D0;
		line-height: 32px;
		padding: 0 10px 0 10px;
		margin: 20px 0 0 0;
	}
	
	#container{
		margin: 10px;
		border: 1px solid #D0D0D0;
		-webkit-box-shadow: 0 0 8px #D0D0D0;
	}
	.error {color: #900; font-weight:bold}
	</style>

</head>

<body>
<div id="container">
<h1>Describe Last.fm user's tastes</h1>
<div id="body">

<form action="" method="POST">
	<label for="username">Last.fm username:</label>
	<input type="text" name="username" /><?php echo form_error('username'); ?>
	<input type="submit" value="Go" />
</form>
<?php if(sizeof($results) > 0) : ?>
		<p><em><?php echo $username; ?></em>'s musical taste is best described by the word:</p>
		<p><strong><a href="<?php echo $results[0]['url']; ?>"><?php echo $results[0]['name']; ?></a></strong> 
        <span class="score">(<?php echo round(( $results[0]['value'] / $total ) * 100, 2); ?>%)</span></p>
		<p>However it can also be described with the words:</p>
		<ol>
			<?php $i = 0; ?>
			<?php foreach ( $results as $result ) : ?>
				<?php if ( $i > 0 ) : ?>
					<li><a href="<?php echo $result['url']; ?>"><?php echo $result['name']; ?></a> 
                    <span class="score">(<?php echo round(( $result['value'] / $total ) * 100, 2); ?>%)</span></li>
				<?php endif; ?>
				<?php $i++; ?>
			<?php endforeach; ?>
		</ol>
<?php endif; ?> 
	</div>
    <p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds</p>
</div>
</body>
</html>