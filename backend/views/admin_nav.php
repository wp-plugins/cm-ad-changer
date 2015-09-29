<style type="text/css">
    .subsubsub li+li:before {content:'| ';}
</style>

<ul class="subsubsub">
    <?php foreach($submenus as $menu): ?>
        <li><a href="<?php echo $menu['link']; ?>" target="<?php echo $menu['target']; ?>" <?php echo ($menu['current']) ? 'class="current"' : ''; ?>><?php echo $menu['title']; ?></a></li>
    <?php endforeach; ?>
</ul>

<br class="clear" />

<h2>
    <div id="icon-<?php echo CMAC_MENU_OPTION; ?>" class="icon32">
        <br />
    </div>
    <?php echo CMAC_NAME . ' - ' . self::$currentSubpage[0] ?>
</h2>

<br class="clear" />

<?php
if( isset($errors) && !empty($errors) )
{
    ?>
    <ul class="msg_error clear">
        <?php
        foreach($errors as $error) echo '<li>' . $error . '</li>';
        ?>
    </ul>
    <?php
}

if( isset($success) && !empty($success) ) :
    ?>
    <div class="msg_success clear"><?php echo $success ?></div>
<?php endif; ?>