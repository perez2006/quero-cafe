<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Quero Cafe') . ' - Quero Cafe') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/style.css')) ?>">
</head>
<body>
<img id="cursor-cafe" src="<?= e(asset_url('imagens/quero.png')) ?>" alt="Quero cafe">
<?= $content ?>
<style>
    body {
        cursor: none;
    }

    #cursor-cafe {
        position: fixed;
        width: 52px;
        height: auto;
        pointer-events: none;
        z-index: 99999;
        left: 0;
        top: 0;
        transform: translate(-10px, -10px);
        user-select: none;
        will-change: transform;
        display: none;
    }
</style>
<script>
    (function () {
        const cursor = document.getElementById('cursor-cafe');

        if (!cursor) {
            return;
        }

        let mouseX = window.innerWidth / 2;
        let mouseY = window.innerHeight / 2;
        let currentX = mouseX;
        let currentY = mouseY;

        document.addEventListener('mousemove', function (e) {
            mouseX = e.clientX;
            mouseY = e.clientY;
            cursor.style.display = 'block';
        });

        document.addEventListener('mouseleave', function () {
            cursor.style.display = 'none';
        });

        function animate() {
            currentX += (mouseX - currentX) * 0.18;
            currentY += (mouseY - currentY) * 0.18;

            cursor.style.left = currentX + 'px';
            cursor.style.top = currentY + 'px';

            requestAnimationFrame(animate);
        }

        animate();
    })();
</script>
</body>
</html>
