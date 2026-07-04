  </main>
</div>

<footer class="text-center text-muted small py-3">
  &copy; <?= date('Y') ?> Hazina Funding — Panneau d'administration
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.getElementById('sidebarToggle')?.addEventListener('click', function () {
    document.getElementById('sidebar').classList.toggle('show');
  });
</script>
</body>
</html>
