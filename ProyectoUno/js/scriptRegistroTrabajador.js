// Funcionalidad de búsqueda y filtrado
document.addEventListener('DOMContentLoaded', function() {
    const buscarInput = document.getElementById('buscarTrabajadores');
    const filtroRol = document.getElementById('filtroRol');
    const tabla = document.getElementById('tablaTrabajadores');
    
    if (tabla) {
        const filas = tabla.querySelectorAll('tbody tr');
        
        function filtrarTabla() {
            const textoBusqueda = buscarInput.value.toLowerCase();
            const valorRol = filtroRol.value;
            
            filas.forEach(fila => {
                const textoFila = fila.textContent.toLowerCase();
                const rol = fila.cells[5].textContent.toLowerCase();
                
                const coincideBusqueda = textoFila.includes(textoBusqueda);
                const coincideRol = valorRol === '' || rol === valorRol;
                
                if (coincideBusqueda && coincideRol) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        }
        
        if (buscarInput) buscarInput.addEventListener('input', filtrarTabla);
        if (filtroRol) filtroRol.addEventListener('change', filtrarTabla);
    }
    
    // Validación del formulario
    const formulario = document.querySelector('.registro-form');
    if (formulario) {
        formulario.addEventListener('submit', function(e) {
            const pin = document.getElementById('pin');
            const numeroControl = document.getElementById('numero_control');
            let hayError = false;
            
            // Validar PIN (4 dígitos numéricos)
            if (pin && (pin.value.length !== 4 || !/^\d+$/.test(pin.value))) {
                e.preventDefault();
                alert('El PIN debe tener exactamente 4 dígitos numéricos.');
                pin.focus();
                hayError = true;
            }
            
            // Validar Número de Control (solo números)
            if (numeroControl && !/^\d+$/.test(numeroControl.value)) {
                e.preventDefault();
                alert('El número de control solo debe contener números.');
                if (!hayError) {
                    numeroControl.focus();
                }
                hayError = true;
            }
        });
        
        // Validación en tiempo real para Número de Control
        const numeroControl = document.getElementById('numero_control');
        if (numeroControl) {
            numeroControl.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
            
            numeroControl.addEventListener('keypress', function(e) {
                // Solo permitir teclas numéricas
                if (!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
            });
        }
        
        // Validación en tiempo real para PIN
        const pin = document.getElementById('pin');
        if (pin) {
            pin.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                
                // Limitar a 4 dígitos
                if (this.value.length > 4) {
                    this.value = this.value.slice(0, 4);
                }
            });
            
            pin.addEventListener('keypress', function(e) {
                // Solo permitir teclas numéricas
                if (!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
                
                // Limitar a 4 dígitos
                if (this.value.length >= 4) {
                    e.preventDefault();
                }
            });
        }
    }
    
    // Toggle sidebar on mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('sidebar-collapsed');
        });
    }
    
    // Handle responsive behavior
    function handleResize() {
        if (window.innerWidth < 992) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('sidebar-collapsed');
        } else {
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('sidebar-collapsed');
        }
    }
    
    // Initial check on page load
    handleResize();
    
    // Add resize listener
    window.addEventListener('resize', handleResize);
});