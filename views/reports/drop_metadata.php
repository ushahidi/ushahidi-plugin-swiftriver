<div style="clear:both;"></div>
<div>
<?php if (count($metadata['places']) > 2): ?>
	<div>
		<strong><?php echo Kohana::lang("swiftriver.associated_locations"); ?></strong><br/>
		<p class="report-when-where">	
			<?php array_shift($metadata['places']); ?>
			<?php foreach ($metadata['places'] as $place): ?>
			    <span class="r_location"><?php echo $place['place_name']; ?></span>
			<?php endforeach; ?>
		</p>
	</div>	
<?php endif; ?>

<?php if (count($metadata['tags'])): ?>
	<style type="text/css">
	    ul.report-tags li{
		    float: left;
		    padding: 3px 5px;
		    margin: 2px 5px 2px 0;
		    border-radius: 3px;
		    -moz-border-radius: 3px;
		    -webkit-border-radius: 3px;
		    background: #3764AA;
		    color: #FFF;
		    list-style-type: none;
		}
	</style>
	<div>
		<strong><?php echo Kohana::lang("swiftriver.tags"); ?></strong></br>
		<ul class="report-tags">
		<?php foreach ($metadata['tags'] as $tag): ?>
			<li><span><?php echo $tag['tag']; ?></span></li>
		<?php endforeach; ?>
		</ul>
		<div style="clear:both"></div>
	</div><br/>
<?php endif; ?>

<?php if (count($metadata['links'])): ?>
	<div>
		<strong><?php echo Kohana::lang('swiftriver.links'); ?></strong><br/>
		<?php foreach ($metadata['links'] as $link): ?>
			<?php
				$attributes = array(
				    'target' => '_blank',
				    'style' => 'padding: 1px 0;',
				    'title' => $link['url']
				);

				// Link
				echo html::anchor($link['url'], $link['url'], $attributes);
			?>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
</div>
