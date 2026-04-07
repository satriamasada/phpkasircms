    </div> <!-- /#main-content -->

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JQuery Moved to Header -->
    
    <script>
        // Theme Toggle Logic
        document.getElementById('themeToggle').addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Update icon
            const icon = document.querySelector('#themeToggle i');
            icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        });

        // Initialize icon on load
        window.addEventListener('DOMContentLoaded', () => {
            const theme = document.documentElement.getAttribute('data-bs-theme');
            const icon = document.querySelector('#themeToggle i');
            if (icon) {
                icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        });

        // Auto-close dismissible alerts after 3 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 3000);
    </script>
</body>
</html>
