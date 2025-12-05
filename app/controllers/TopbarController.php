<?php
class TopbarController {
    public function render() {
        include 'app/views/layouts/topbar.php';
        echo '<link rel="stylesheet" href="assets/css/topbar.css">';
    }
}
?>
