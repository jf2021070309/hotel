# Módulo: Cuadro de Reservas
# Ticket: HP-8 — Corregir scroll visual / Carga instantánea de Cuadro de Reservas
# DoR: https://docs.google.com/presentation/d/1BCuNJj2j8wj8N9fujQbFdzAoVKyJc5z7fPK9Fpifa2Q/edit

Feature: HP-8 - Carga instantánea del Cuadro de Reservas

  Como recepcionista del hotel
  Quiero que el cuadro de reservas muestre el día de hoy automáticamente al abrirse
  Para atender al huésped sin necesidad de hacer scroll manual

  Scenario: El calendario muestra el día actual al abrir el módulo por primera vez
    Given que el recepcionista ha iniciado sesión en el sistema
    When navega al módulo "Cuadro de Reservas"
    Then el calendario debe estar posicionado visualmente en el día de hoy
    And no debe requerir scroll manual para ver la fecha actual

  Scenario: El calendario vuelve al día actual al regresar al módulo
    Given que el recepcionista navegó a otro módulo del sistema
    When regresa al módulo "Cuadro de Reservas"
    Then el calendario se posiciona automáticamente en la fecha actual

  Scenario: El desplazamiento ocurre de forma instantánea
    Given que el recepcionista está en el módulo "Cuadro de Reservas"
    Then el scroll al día de hoy debe completarse en menos de 1000ms

  Scenario: El comportamiento no rompe otras funcionalidades del calendario
    Given que el recepcionista está en el módulo "Cuadro de Reservas"
    When el calendario se posiciona en el día actual
    Then las reservas existentes deben seguir visualizándose correctamente
    And los colores de estado de habitaciones deben ser los correctos
