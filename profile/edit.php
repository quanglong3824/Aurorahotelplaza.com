<?php
require_once __DIR__ . '/../controllers/ProfileController.php';
require_once __DIR__ . '/../helpers/language.php';

session_start();
initLanguage();

$controller = new ProfileController();
$data = $controller->edit();
extract($data);

?>
<!DOCTYPE html>
<html translate="no" class="light" lang="<?php echo getLang(); ?>">
<head>
    <meta name="google" content="notranslate" />
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('profile_page.edit_title'); ?></title>
    <link href="../assets/css/tailwind-output.css" rel="stylesheet" />
    <link href="../assets/css/fonts.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/liquid-glass.css">
    <link rel="stylesheet" href="../assets/css/pages-glass.css">
</head>
<body class="glass-page font-body text-white">
    <div class="relative flex min-h-screen w-full flex-col">
        <?php include '../includes/header.php'; ?>
        
        <?php include '../views/profile/edit.php'; ?>

        <?php include '../includes/footer.php'; ?>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
