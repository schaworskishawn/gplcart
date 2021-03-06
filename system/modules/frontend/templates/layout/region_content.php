<?php
/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 * @var $this \gplcart\core\controllers\frontend\Controller
 * To see available variables <?php print_r(get_defined_vars()); ?>
 */
?>
<?php if (empty($region_content)) { ?>
<div class="empty"><?php echo $this->text('Content coming soon...'); ?></div>
<?php } else { ?>
<?php foreach ($region_content as $item) { ?>
<?php echo $item; ?>
<?php } ?>
<?php } ?>