<?php
/**
 * Footer Layout Template — Design ISPSN
 */
?>
</main> <!-- end .main -->
</div> <!-- end .wrap -->

<div class="toast" id="toast"></div>

<script>
window.switchRole = async (role) => {
    await fetch('index.php?api=switch_role&role=' + role);
    location.reload();
};
</script>
<script src="assets/js/cobertura.js?v=<?= time() ?>"></script>
</body>
</html>
