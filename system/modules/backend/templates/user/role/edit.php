<?php
/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */
?>
<form method="post" class="form-horizontal">
  <input type="hidden" name="token" value="<?php echo $_token; ?>">
  <fieldset>
    <div class="form-group">
      <label class="col-md-2 control-label"><?php echo $this->text('Status'); ?></label>
      <div class="col-md-4">
        <div class="btn-group" data-toggle="buttons">
          <label class="btn btn-default<?php echo empty($role['status']) ? '' : ' active'; ?>">
            <input name="role[status]" type="radio" autocomplete="off" value="1"<?php echo empty($role['status']) ? '' : ' checked'; ?>>
            <?php echo $this->text('Enabled'); ?>
          </label>
          <label class="btn btn-default<?php echo empty($role['status']) ? ' active' : ''; ?>">
            <input name="role[status]" type="radio" autocomplete="off" value="0"<?php echo empty($role['status']) ? ' checked' : ''; ?>>
            <?php echo $this->text('Disabled'); ?>
          </label>
        </div>
        <div class="help-block"><?php echo $this->text('Disabled roles will not be available for users'); ?></div>
      </div>
    </div>
    <div class="form-group required<?php echo $this->error('name', ' has-error'); ?>">
      <label class="col-md-2 control-label"><?php echo $this->text('Name'); ?></label>
      <div class="col-md-4">
        <input maxlength="255" name="role[name]" class="form-control" value="<?php echo isset($role['name']) ? $this->e($role['name']) : ''; ?>">
        <div class="help-block">
          <?php echo $this->error('name'); ?>
          <div class="text-muted">
            <?php echo $this->text('Required. A descriptive name of the role, e.g Boss, Manager etc'); ?>
          </div>
        </div>
      </div>
    </div>
    <div class="form-group<?php echo $this->error('redirect', ' has-error'); ?>">
      <label class="col-md-2 control-label"><?php echo $this->text('Redirect'); ?></label>
      <div class="col-md-4">
        <input maxlength="255" name="role[redirect]" placeholder="<?php echo $this->text('To account page'); ?>" class="form-control" value="<?php echo isset($role['redirect']) ? $this->e($role['redirect']) : ''; ?>">
        <div class="help-block">
          <?php echo $this->error('redirect'); ?>
          <div class="text-muted">
            <?php echo $this->text('A destination that a user is redirected to after logging in'); ?>
          </div>
        </div>
      </div>
    </div>
  </fieldset>
  <fieldset>
    <legend><?php echo $this->text('Permissions'); ?></legend>
    <div class="form-group">
      <div class="col-md-10 col-md-offset-2">
        <div class="row">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <input type="checkbox" id="select-all" autocomplete="off"><?php echo $this->text('Select/unselect all'); ?>
              </label>
            </div>
          </div>
          <?php foreach ($permissions as $permission_group) { ?>
          <div class="col-md-3">
            <?php foreach ($permission_group as $id => $name) { ?>
            <div class="checkbox">
              <label>
                <?php if (!empty($role['permissions']) && in_array($id, $role['permissions'])) { ?>
                <input type="checkbox" class="select-all" name="role[permissions][]" value="<?php echo $id; ?>" checked>
                <?php } else { ?>
                <input type="checkbox" class="select-all" name="role[permissions][]" value="<?php echo $id; ?>">
                <?php } ?>
                <?php echo $this->e($name); ?>
              </label>
            </div>
            <?php } ?>
          </div>
          <?php } ?>
        </div>
      </div>
    </div>
  </fieldset>
  <div class="form-group">
    <div class="col-md-10 col-md-offset-2">
      <?php if (isset($role['role_id']) && $this->access('user_role_delete')) { ?>
      <button class="btn btn-danger delete" name="delete" value="1" onclick="return confirm(GplCart.text('Are you sure? It cannot be undone!'));">
        <?php echo $this->text('Delete'); ?>
      </button>
      <?php } ?>
      <a href="<?php echo $this->url('admin/user/role'); ?>" class="btn btn-default cancel">
        <?php echo $this->text('Cancel'); ?>
      </a>
      <?php if ($this->access('user_role_edit') || $this->access('user_role_add')) { ?>
      <button class="btn btn-default save" name="save" value="1">
        <i class="fa fa-floppy-o"></i> <?php echo $this->text('Save'); ?>
      </button>
      <?php } ?>
    </div>
  </div>
</form>