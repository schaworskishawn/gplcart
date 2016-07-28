<div class="row">
  <form method="post" id="login" class="login col-md-6<?php echo $this->error(null, ' form-errors'); ?>">
    <input type="hidden" name="token" value="<?php echo $token; ?>">
    <div class="form-group<?php echo $this->error('email', ' has-error'); ?>">
      <label><?php echo $this->text('E-mail'); ?></label>
      <input type="email" class="form-control" name="user[email]" value="<?php echo isset($user['email']) ? $user['email'] : ''; ?>" autofocus required>
      <?php if ($this->error('email', true)) { ?>
      <div class="help-block"><?php echo $this->error('email'); ?></div>
      <?php } ?>
    </div>
    <div class="form-group<?php echo $this->error('password', ' has-error'); ?>">
      <label><?php echo $this->text('Password'); ?></label>
      <input class="form-control" type="password" name="user[password]" required>
      <?php if ($this->error('password', true)) { ?>
      <div class="help-block"><?php echo $this->error('password'); ?></div>
      <?php } ?>
    </div>
    <div class="form-group">
      <button class="btn btn-primary btn-block" name="login" value="1"><?php echo $this->text('Log in'); ?></button>
    </div>
    <div class="form-group">
      <a href="<?php echo $this->url('register'); ?>"><?php echo $this->text('Register account'); ?></a> |
      <a href="<?php echo $this->url('forgot'); ?>"><?php echo $this->text('Forgot password'); ?></a>
    </div>
    <input name="url" style="position:absolute;top:-999px;" value="">
  </form>
</div>