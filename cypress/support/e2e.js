// cypress/support/e2e.js
// Comando personalizado para login en Hotel Platinium (sesión PHP vía API)

Cypress.Commands.add("loginHotel", (usuario = "roy", password = "admin123") => {
  cy.request({
    method: "POST",
    url: "/api/auth/login.php",
    body: { usuario, password },
    headers: { "Content-Type": "application/json" },
    failOnStatusCode: false,
  }).then((res) => {
    // La API devuelve token/sesión — la cookie se setea automáticamente
    cy.log(`Login status: ${res.status}`);
  });
});
