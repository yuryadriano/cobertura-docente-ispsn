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

window.switchAnoLectivo = async (ano) => {
    try {
        const res = await fetch('index.php?api=salvar_ano_lectivo', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ ano_lectivo: ano })
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Falha ao alterar o ano lectivo.');
        }
    } catch (err) {
        alert('Erro de comunicação ao alterar o ano lectivo.');
    }
};
</script>
<script src="assets/js/cobertura.js?v=<?= time() ?>"></script>
</body>
</html>
