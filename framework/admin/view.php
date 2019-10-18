<?php defined('ABSPATH') or die; ?>

<div class="container">
    
    <h2 class="title"><?php
        print preg_replace( '/<a /','<a class="author" target="_blank" ',$plugin_data['Title']) ?> [v<?php
        print $plugin_data['Version'] ?>]</h2>
    <!--p><span class="author"><?php print $plugin_data['AuthorName'] ?></span></p-->
    <?php //var_dump($plugin_data) ?>
</div>

<div class="container">
    <h2 class="title"><?php print __('Active Applications','coders_framework') ?></h2>
<?php if( count( $instances )) : ?>
<ul class="list">
    <?php foreach( $instances as $instance => $atts ) : ?>
        <li class="app-box">
            <a href="<?php printf('%s/%s', get_site_url(), $atts['end-point']); ?>" target="_blank" class="app-name">
                <?php print $instance ?>
            </a>
            <span class="status"><?php print $atts['class'] ?></span>
        </li>
    <?php endforeach; ?>
</ul>
<?php else: ?>
    <p><?php print __('No applications detected','coders_framework'); ?></p>
<?php endif; ?>
</div>

<div class="container">
    <h2 class="title"><?php print __('Repository Setup','coders_framework') ?></h2>
    <form name="coders-repo" action="" method="post">
        <input type="text"
               name="coders_root_path"
               placeholder="<?php print strlen($repo_path) ?
                       __('Set repository path','coders_framework') :
                       __('No repository set','coders_framework') ?>"
               value="<?php print $repo_path ?>" />
        <button class="button" type="submit" name="_action" value="set_root"><?php
            print __('Set root path','coders_framework') ?></button>
    </form>
</div>

<div class="container">
    <h2 class="title"><?php print __('Debug Request Data','coders_framework') ?></h2>
    <?php print_r($request) ?>
</div>