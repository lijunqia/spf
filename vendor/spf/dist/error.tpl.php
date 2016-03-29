<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></title>
	<?php
	// Unique error identifier
	$error_id = uniqid('error');
	?>
	<style type="text/css">
		#error_div {
			background: #ddd;
			font-size: 1em;
			font-family: sans-serif;
			text-align: left;
			color: #111;
		}
		#error_div h1,#error_div h2 {
			margin: 0;
			padding: 1em;
			font-size: 1em;
			font-weight: normal;
			background: #911;
			color: #fff;
		}
		#error_div h1 a,#error_div h2 a {
			color: #fff;
		}
		#error_div h2 {
			background: #222;
		}
		#error_div h3 {
			margin: 0;
			padding: 0.4em 0 0;
			font-size: 1em;
			font-weight: normal;
		}
		#error_div p {
			margin: 0;
			padding: 0.2em 0;
		}
		#error_div a {
			color: #1b323b;
		}
		#error_div pre {
			overflow: auto;
			white-space: pre-wrap;
		}
		#error_div table {
			width: 100%;
			display: block;
			margin: 0 0 0.4em;
			padding: 0;
			border-collapse: collapse;
			background: #fff;
		}
		#error_div table td {
			border: solid 1px #ddd;
			text-align: left;
			vertical-align: top;
			padding: 0.4em;
		}
		#error_div div.content {
			padding: 0.4em 1em 1em;
			overflow: hidden;
		}
		#error_div pre.source {
			margin: 0 0 1em;
			padding: 0.4em;
			background: #fff;
			border: dotted 1px #b7c680;
			line-height: 1.2em;
		}
		#error_div pre.source span.line {
			display: block;
		}
		#error_div pre.source span.highlight {
			background: #f0eb96;
		}
		#error_div pre.source span.line span.number {
			color: #666;
		}
		#error_div ol.trace {
			display: block;
			margin: 0 0 0 2em;
			padding: 0;
			list-style: decimal;
		}
		#error_div ol.trace li {
			margin: 0;
			padding: 0;
		}
		.js .collapsed {
			display: none;
		}
	</style>
	<script type="text/javascript">
		document.documentElement.className = 'js';
		function koggle(elem)
		{
			elem = document.getElementById(elem);
			if (elem.style && elem.style['display'])
			// Only works with the "style" attr
				var disp = elem.style['display'];
			else if (elem.currentStyle)
			// For MSIE, naturally
				var disp = elem.currentStyle['display'];
			else if (window.getComputedStyle)
			// For most other browsers
				var disp = document.defaultView.getComputedStyle(elem, null).getPropertyValue('display');
			// Toggle the state of the "display" style
			elem.style.display = disp == 'block' ? 'none' : 'block';
			return false;
		}
	</script>
	<div id="error_div">
		<h1><span class="type"><?=get_class($e)?> [ <?=$code?> ]:</span>
			<span class="message"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8')?></span></h1>
		<div id="<?=$error_id?>" class="content">
			<p><span class="file">
<?=self::debugPath($e->getFile())?>[<?=$e->getLine()?>]
</span></p>
			<?=self::debugSource($e->getFile(), $e->getLine())?>
			<ol class="trace">
				<?php foreach (self::trace($trace) as $i => $step):?>
					<li>
						<p>

<span class="file">
<?php
if ($step['file']):
	$source_id = "{$error_id}source{$i}";
	?>
	<a href="#<?=$source_id?>"
	   onclick="return koggle('<?=$source_id?>')">
		<?=self::debugPath($step['file'])?> [ <?=$step['line']?> ]</a>
<?php else:?>
	{PHP internal call}
<?php endif;?>
</span>&raquo;

							<?=$step['function']?>(
							<?php
							if ($step['args']):
								$args_id = $error_id . 'args' . $i;
								?>
								<a href="#<?php echo $args_id?>" onclick="return koggle('<?php echo $args_id?>')"><?='arguments'?></a>
							<?php endif ?>)
						</p>
						<?php if (isset($args_id)):?>
							<div id="<?php echo $args_id?>" class="collapsed">
								<table cellspacing="0">
									<?php foreach ($step['args'] as $name => $arg):?>
										<tr>
											<td><code><?php echo $name?></code></td>
											<td><pre><?php	echo self::dump($arg)?></pre></td>
										</tr>
									<?php	endforeach?>
								</table>
							</div>
						<?php endif ?>
						<?php
						if (isset($source_id)):
							?>
							<pre id="<?php echo $source_id?>" class="source collapsed"><code><?php	echo $step['source']?></code></pre>
						<?php endif ?>
					</li>
					<?php
					unset($args_id, $source_id);
					?>
					<?php
				endforeach
				?>
			</ol>
		</div>
		<h2><a href="#<?php echo $env_id = $error_id . 'environment'?>"
		       onclick="return koggle('<?php echo $env_id?>')"><?php echo 'Environment'?></a></h2>
		<div id="<?php echo $env_id?>" class="content collapsed">
			<?php $included = get_included_files()?>
			<h3><a href="#<?php echo $env_id = $error_id . 'environment_included'?>"
			       onclick="return koggle('<?php echo $env_id?>')"><?php echo 'Included files';?></a> (<?php echo count($included)?>)</h3>
			<div id="<?php echo $env_id?>" class="collapsed">
				<table cellspacing="0">
					<?php foreach ($included as $file):?>
						<tr>
							<td><code><?php	echo self::debugPath($file)?></code></td>
						</tr>
					<?php endforeach?>
				</table>
			</div>
			<?php $included = get_loaded_extensions()?>
			<h3><a href="#<?php	echo $env_id = $error_id . 'environment_loaded'?>"
			       onclick="return koggle('<?php
			       echo $env_id?>')"><?php
					echo 'Loaded extensions'?></a> (<?php
				echo count($included)?>)</h3>
			<div id="<?php echo $env_id?>" class="collapsed">
				<table cellspacing="0">
					<?php foreach ($included as $file):?>
						<tr>
							<td><code><?=self::debugPath($file)?></code></td>
						</tr>
					<?php endforeach ?>
				</table>
			</div>
			<?php
			foreach (array(
				         '_SESSION',
				         '_GET',
				         '_POST',
				         '_FILES',
				         '_COOKIE',
				         '_SERVER'
			         ) as $var):
				if (empty($GLOBALS[$var]) or ! is_array($GLOBALS[$var]))
					continue;
				?>
				<h3><a href="#<?=$env_id = $error_id . 'environment' . strtolower($var)?>"
				       onclick="return koggle('<?=$env_id?>')">$<?=$var?></a></h3>
				<div id="<?php echo $env_id?>" class="collapsed">
					<table cellspacing="0">
						<?php foreach($GLOBALS[$var] as $key => $value):?>
							<tr>
								<td><code><?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?></code></td>
								<td><pre><?=self::dump($value)?></pre></td>
							</tr>
						<?php endforeach?>
					</table>
				</div>
			<?php endforeach?>
		</div>
	</div>