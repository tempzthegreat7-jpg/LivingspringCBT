 <?php if (isset($errors)): ?>
     <?php foreach ($errors as $error): ?>
         <p class="errors"><?= $error ?></p>
     <?php endforeach; ?>
 <?php endif; ?>