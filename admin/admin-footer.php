</div><!-- /.admin-content-container -->
        </div><!-- /.admin-content -->
    </div><!-- /.admin-wrapper -->
    
    <!-- Footer -->
    <footer class="admin-footer py-3 mt-auto bg-white border-top">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> RoundTours. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">Admin Panel v1.0</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Admin JavaScript -->
    <script>
        // Toggle sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const adminWrapper = document.querySelector('.admin-wrapper');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    adminWrapper.classList.toggle('sidebar-collapsed');
                });
            }
            
            // Check for mobile devices and collapse sidebar by default
            if (window.innerWidth < 992) {
                adminWrapper.classList.add('sidebar-collapsed');
            }
            
            // Responsive behavior
            window.addEventListener('resize', function() {
                if (window.innerWidth < 992) {
                    adminWrapper.classList.add('sidebar-collapsed');
                }
            });
        });/**
 * Admin JavaScript for RoundTours
 */

// Toggle sidebar functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const adminLayout = document.querySelector('.admin-layout');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            adminLayout.classList.toggle('sidebar-collapsed');
            
            // Save sidebar state to localStorage
            const collapsed = adminLayout.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebar_collapsed', collapsed ? 'true' : 'false');
        });
    }
    
    // Restore sidebar state from localStorage
    const savedState = localStorage.getItem('sidebar_collapsed');
    if (savedState === 'true') {
        adminLayout.classList.add('sidebar-collapsed');
    } else if (savedState === 'false') {
        adminLayout.classList.remove('sidebar-collapsed');
    }
    
    // Check for mobile devices and collapse sidebar by default on small screens
    function checkScreenSize() {
        if (window.innerWidth < 992) {
            adminLayout.classList.remove('sidebar-collapsed');
            // On mobile, we want the opposite behavior - sidebar should be hidden by default
            // because the sidebar is already translated off-screen in CSS for mobile
        }
    }
    
    // Initial check
    checkScreenSize();
    
    // Add click outside to close sidebar on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth < 992 && 
            adminLayout.classList.contains('sidebar-collapsed')) {
            
            const sidebar = document.querySelector('.admin-sidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            
            // If click is outside sidebar and not on toggle button
            if (!sidebar.contains(e.target) && e.target !== toggleBtn && !toggleBtn.contains(e.target)) {
                adminLayout.classList.remove('sidebar-collapsed');
            }
        }
    });
    
    // Responsive behavior
    window.addEventListener('resize', checkScreenSize);
});
    </script>
    
    <!-- Any additional JavaScript -->
    <?php if (isset($additional_js)): ?>
        <?php echo $additional_js; ?>
    <?php endif; ?>
</body>
</html>