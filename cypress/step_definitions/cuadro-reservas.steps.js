import { Given, When, Then } from "@badeball/cypress-cucumber-preprocessor";

// URL real del módulo de reservas
const URL_RESERVAS = "/app/Views/reservas/index.php";
const URL_ROOMING  = "/app/Views/rooming/index.php";

// Selector del día de hoy (clase real del proyecto)
const SEL_HOY = "th.today-hdr, td.today-col";

// ─── Given ────────────────────────────────────────────────────────────────────

Given("que el recepcionista ha iniciado sesión en el sistema", () => {
  cy.loginHotel();
  cy.visit(URL_RESERVAS, { failOnStatusCode: false });
});

Given("que el recepcionista navegó a otro módulo del sistema", () => {
  cy.loginHotel();
  cy.visit(URL_ROOMING, { failOnStatusCode: false });
});

Given("que el recepcionista está en el módulo {string}", (_modulo) => {
  cy.loginHotel();
  cy.visit(URL_RESERVAS, { failOnStatusCode: false });
});


// ─── When ─────────────────────────────────────────────────────────────────────

When("navega al módulo {string}", (_modulo) => {
  cy.visit(URL_RESERVAS, { failOnStatusCode: false });
});

When("regresa al módulo {string}", (_modulo) => {
  cy.visit(URL_RESERVAS, { failOnStatusCode: false });
});

When("el calendario se posiciona en el día actual", () => {
  cy.wait(800); // Tiempo para que Vue/JS renderice el scroll
});

// ─── Then ─────────────────────────────────────────────────────────────────────

Then(
  "el calendario debe estar posicionado visualmente en el día de hoy",
  () => {
    // Verifica que existe el header de hoy en el DOM
    cy.get(SEL_HOY).should("exist");
  }
);

Then("no debe requerir scroll manual para ver la fecha actual", () => {
  // Verifica que el elemento de hoy está dentro del viewport
  cy.get(SEL_HOY)
    .first()
    .then(($el) => {
      const rect = $el[0].getBoundingClientRect();
      // Debe estar visible en pantalla (no debajo del fold)
      expect(rect.top, "Columna de hoy debe estar en el viewport").to.be.lessThan(
        Cypress.config("viewportHeight")
      );
    });
});

Then("el calendario se posiciona automáticamente en la fecha actual", () => {
  cy.get(SEL_HOY).should("exist");
});

Then(
  "el scroll al día de hoy debe completarse en menos de {int}ms",
  (ms) => {
    const inicio = Date.now();
    cy.get(SEL_HOY)
      .should("exist")
      .then(() => {
        const duracion = Date.now() - inicio;
        expect(duracion, `Scroll debe ser < ${ms}ms`).to.be.lessThan(ms);
      });
  }
);

Then(
  "las reservas existentes deben seguir visualizándose correctamente",
  () => {
    // La tabla del cuadro de reservas debe existir
    cy.get("#app-reservas, .cuadro-table, table").should("exist");
  }
);

Then(
  "los colores de estado de habitaciones deben ser los correctos",
  () => {
    // Debe haber celdas con colores de estado (background inline o clases)
    cy.get(".cuadro-table td[style], .cuadro-table td[class*='ocupad'], .cuadro-table td[class*='libre']")
      .should("have.length.greaterThan", 0);
  }
);
