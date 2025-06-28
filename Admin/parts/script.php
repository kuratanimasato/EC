</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous">
</script>
<script>
window.addEventListener('DOMContentLoaded', () => {
  const messages = document.querySelectorAll('.fade-message');
  messages.forEach(msg => {
    setTimeout(() => {
      msg.style.transition = 'opacity 0.5s ease';
      msg.style.opacity = '0';
      setTimeout(() => msg.remove(), 500); // DOMから削除
    }, 5000);
  });
});
</script>
</body>

</html>