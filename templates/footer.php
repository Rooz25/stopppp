<?php
// The bottom of every page. Closes the <main> that header.php opened.
// The footer text and the styling come from the index.php of Rooz and Gijs.
?>
</main>

<footer>AA KOOK! &middot; Samen sneller en makkelijker koken</footer>

<?php
// TEMPORARY: the hamburger menu for the mobile version.
// style.css hides the menu under 720px (.navbar nav { display: none }) and only
// shows it again when the nav has the class "open". So without this script you
// cannot open the menu on a phone.
// In step 12 script.js gets cleaned up and this little script moves into there.
?>
<script>
    document.getElementById('menuButton').onclick = function () {
        document.getElementById('menu').classList.toggle('open');
    };
</script>
</body>
</html>
