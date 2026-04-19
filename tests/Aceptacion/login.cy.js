describe('Pruebas de Aceptación - Humo', () => {
  it('Debe cargar la página de inicio de sesión correctamente', () => {
    cy.visit('/login.php');
    cy.get('.brand-logo').should('contain', 'PLATINIUM');
    cy.get('input[placeholder="Nombre de usuario"]').should('be.visible');
    cy.get('input[placeholder="••••••••"]').should('be.visible');
  });

  it('Debe mostrar error con credenciales inválidas', () => {
    cy.visit('/login.php');
    cy.get('input[placeholder="Nombre de usuario"]').type('usuario_inexistente');
    cy.get('input[placeholder="••••••••"]').type('123456');
    cy.get('button[type="submit"]').click();
    cy.get('.alert-danger').should('be.visible');
  });

  it('Debe iniciar sesión con credenciales válidas (Escenario Roy)', () => {
    cy.visit('/login.php');
    cy.get('input[placeholder="Nombre de usuario"]').type('roy');
    cy.get('input[placeholder="••••••••"]').type('admin123');
    cy.get('button[type="submit"]').click();

    // Verificamos que ya no estemos en login y que haya cargado el sistema
    cy.url().should('not.include', 'login.php');
    // Buscamos rastro del usuario o dashboard (ajustar según UI real)
    cy.get('body').should('be.visible');
  });
});
