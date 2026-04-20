describe('Flujo Completo de Check-in e Integración Financiera', () => {
  const GUEST_NAME = 'HUESPED ' + new Date().getTime();
  const GUEST_DOC = '99998888';
  const PAY_AMOUNT = '150.00';

  beforeEach(() => {
    // 1. Login inicial
    cy.visit('/login.php');
    cy.get('input[placeholder="Nombre de usuario"]').type('roy');
    cy.get('input[placeholder="••••••••"]').type('admin123');
    cy.get('button[type="submit"]').click();
    cy.url().should('not.include', 'login.php');
  });

  it('Debe realizar un check-in y verificar que el dinero entre a Flujo de Caja', () => {
    // Cálculo dinámico del turno según reglas de FinanzasHelper (6:00-14:00 Mañana, resto Tarde)
    const hour = new Date().getHours();
    const currentShift = (hour >= 6 && hour < 14) ? 'MAÑANA' : 'TARDE';

    // 1. Verificar Estado de Caja (Apertura o Validación de Turno)
    cy.visit('/app/Views/flujo/index.php');
    
    cy.url().then((url) => {
        // Si no redirigió a flujo/form, la caja está cerrada
        if (!url.includes('flujo/form')) {
            cy.contains('button', 'NUEVO TURNO').click();
            
            // Seleccionamos el turno correcto haciendo clic en el label correspondiente (Radio Buttons)
            const labelShift = currentShift === 'MAÑANA' ? '#lbl-manana' : '#lbl-tarde';
            cy.get(labelShift, { timeout: 8000 }).should('be.visible').click();
            
            cy.get('.swal2-confirm').click();
            
            cy.url().should('include', 'flujo/form');
            cy.log(`✅ Caja de turno ${currentShift} abierta automáticamente por Cypress`);
        } else {
            // Si ya está abierta, validamos que el turno activo en el select coincida con la hora actual
            cy.get('select').first().should('have.value', currentShift).then(() => {
                cy.log(`✅ Validado: Turno ${currentShift} ya estaba abierto correctamente`);
            });
        }
    });

    // 2. Ir al módulo de Rooming
    cy.visit('/app/Views/rooming/index.php');
    cy.contains('Rooming / Check-in').should('be.visible');

    // 3. Abrir modal de Check-in
    cy.contains('button', 'NUEVO').click();
    cy.get('#modalCheckin').should('be.visible');

    // 4. Llenar Formulario de Check-in
    // Asegurarse de que hay habitaciones disponibles
    cy.get('#inputHabitacion option', { timeout: 10000 }).should('have.length.gt', 1);
    cy.get('#inputHabitacion').select(1);
    
    // PRIMERO: Documento (esto dispara el autocompletado)
    cy.get('#inputDocumentoHuesped').clear().type(GUEST_DOC);
    // Esperamos un momento a que el API de clientes responda/procese
    cy.wait(800); 
    
    // SEGUNDO: Nombre completo (lo llenamos al final para asegurar que no se borre)
    cy.get('#inputNombreHuesped').clear().type(GUEST_NAME).should('have.value', GUEST_NAME);
    
    // Configurar Pago
    cy.get('#inputMontoPago').clear().type(PAY_AMOUNT).should('have.value', PAY_AMOUNT);
    cy.get('#inputMetodoPago').select(1);
    
    // Interceptamos el registro para esperar la respuesta real
    cy.intercept('POST', '**/api/rooming.php?action=checkin').as('resCheckin');

    // El botón debe estar habilitado antes de hacer click
    cy.get('#btnRegistrarCheckin').should('not.be.disabled').click({ force: true });

    // 5. Verificar éxito en Rooming
    cy.wait('@resCheckin', { timeout: 15000 }).then((interception) => {
        const body = interception.response.body;
        if (!body.ok) {
            cy.log('❌ ERROR DEL SERVIDOR: ' + body.msg);
        }
        expect(interception.response.statusCode).to.eq(200);
    });

    cy.get('#modalCheckin', { timeout: 10000 }).should('not.be.visible');
    
    // Verificamos que el nombre aparezca en la tabla principal
    cy.get('table', { timeout: 8000 }).should('contain', GUEST_NAME);

    // 6. VALIDACIÓN CRUZADA: Flujo de Caja
    // Interceptamos la carga de datos para esperar la sincronización real
    cy.intercept('GET', '**/api/flujo.php?action=detalle*').as('getCaja');
    
    cy.visit('/app/Views/flujo/index.php');
    
    // El sistema debería redirigir automáticamente al turno abierto
    cy.url().should('include', 'flujo/form');
    
    // ESPERAMOS a que el API responda y Vue renderice
    cy.wait('@getCaja', { timeout: 10000 });

    // Buscar en la tabla de INGRESOS el monto y el nombre
    cy.get('h6').contains('INGRESOS', { timeout: 15000 }).closest('.card').within(() => {
        // Esperamos un segundo a que Vue termine de renderizar los datos del API
        cy.wait(2000);

        // Verificamos que el nombre del huésped aparezca en algún lugar de la tabla (como texto o valor de campo)
        cy.get('table').then(($table) => {
            const html = $table.text();
            // Capturamos valores de inputs Y textareas
            const fields = $table.find('input, textarea').toArray().map(f => f.value).join(' ');
            const allContent = (html + ' ' + fields).toUpperCase();
            
            cy.log('Contenido total detectado:', allContent);
            
            expect(allContent, `Buscando ${GUEST_NAME} en el contenido de la tabla`).to.include(GUEST_NAME.toUpperCase());
            expect(allContent, 'Buscando el monto de 150').to.match(/150(\.00)?/);
        });
    });

    cy.log('✅ Integración Rooming-Flujo validada con éxito');
  });
});
