# Documentación Completa del Sistema
## Chatbot de Pedidos para Restaurante (MVP)

**Metodología:** Scrum  
**Versión:** 1.0  
**Autor:** Javier Cabezas Nina

---
## Índice

1. [Descripción General del Proyecto](#1-descripción-general-del-proyecto)
2. [Stack Tecnológico](#2-stack-tecnológico)
3. [Historias de Usuario y Criterios de Aceptación](#3-historias-de-usuario-y-criterios-de-aceptación)
4. [Product Backlog](#4-product-backlog)
5. [Sprint Backlog](#5-sprint-backlog)
6. [Definition of Done](#6-definition-of-done)
7. [Diagrama de Casos de Uso](#7-diagrama-de-casos-de-uso)
8. [Modelo Entidad-Relación](#8-modelo-entidad-relación)
9. [Diagrama de Arquitectura de Componentes](#9-diagrama-de-arquitectura-de-componentes)
10. [Flujo Conversacional del Bot](#10-flujo-conversacional-del-bot)
11. [Máquina de Estados del Pedido](#11-máquina-de-estados-del-pedido)
12. [Manual de Usuario - Cliente](#12-manual-de-usuario---cliente)
13. [Manual de Usuario - Delivery](#13-manual-de-usuario---delivery)
14. [Manual de Usuario - Administrador](#14-manual-de-usuario---administrador)
15. [Manual de Despliegue](#15-manual-de-despliegue)
16. [Bitácora de Decisiones Técnicas](#16-bitácora-de-decisiones-técnicas)
17. [Limitaciones Conocidas del MVP](#17-limitaciones-conocidas-del-mvp)

## 1. Descripción General del Proyecto

### 1.1 Objetivo

Desarrollar un MVP funcional para la gestión de pedidos de un restaurante, compuesto por dos partes integradas:

| Parte | Descripción |
|-------|-------------|
| **Bot de Telegram** | Atiende a clientes (menú, pedido, pago, seguimiento) y repartidores (asignación, ubicación, entrega). |
| **Panel de Administración Web** | Configuración del menú, control de disponibilidad, tablero de pedidos, confirmación de pagos, asignación de repartidores y reportes. |

### 1.2 Roles del Sistema

| Rol | Descripción |
|-----|-------------|
| **Cliente** | Persona que realiza pedidos desde el bot de Telegram. |
| **Delivery (Repartidor)** | Persona que recibe asignaciones y entrega pedidos. |
| **Administrador** | Persona que gestiona el sistema desde el panel web. |

### 1.3 Alcance

El sistema debe permitir:

- **Al cliente:** Ver menú, armar pedido, enviar ubicación, pagar con QR, recibir notificaciones.
- **Al repartidor:**  recibir asignaciones, enviar ubicación en vivo, confirmar llegada y entrega.
- **Al administrador:** Gestionar platos, programar menú , gestionar pedidos, confirmar pagos, asignar repartidores, ver reportes.

---

## 2. Stack Tecnológico

| Componente | Tecnología | Justificación |
|------------|------------|---------------|
| **Lenguaje Backend** | [PHP]  | [Lenguaje orientado a POO] |
| **Framework Backend** | [LARAVEL] |  [Framework robusto que permite trabajar con routing avanzado, ORM (Eloquent), autenticación segura, comandos mediante Artisan y gestión eficiente de bases de datos mediante migraciones y seeders. ] |
| **Base de Datos** | [MYSQL]  | [Sistema de gestión de bases de datos relacional (RDBMS) de código abierto, altamente eficiente y confiable para el modelado, almacenamiento y consulta estructurada de datos mediante SQL.] |

### Variables de Entorno

| Variable | Descripción | Ejemplo |
|----------|-------------|---------|
| `TELEGRAM_TOKEN` | Token del bot de Telegram | `123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11` |
| `DATABASE_URL` | URL de conexión a la base de datos | `mysql://user:pass@localhost:3306/db` |
| `ADMIN_USER` | Usuario del panel de administración | `root` |
| `ADMIN_PASSWORD_HASH` | Hash de la contraseña | `` |
| `QR_API_URL` | URL para generar códigos QR | `https://api.qrserver.com/v1/create-qr-code/` |

## 3. Historias de Usuario y Criterios de Aceptación

### 🟢 Épica 1: Bot - Cliente

#### HU-C-01: Ver menú del día

**Como** cliente,  
**quiero** ver el menú del día con los platos disponibles y sus precios,  
**para** poder elegir qué deseo pedir.

**Criterios de Aceptación:**
1. Dado que soy un cliente y escribo `/start`, cuando el bot responde, entonces veo el menú del día con platos y precios.
2. Dado que el administrador actualiza el menú en el panel, cuando el cliente inicia el bot, entonces solo ve los platos habilitados para la fecha actual.
3. Dado que un plato tiene stock = 0, cuando el cliente consulta el menú, entonces ese plato no aparece en el menú.

---
#### HU-C-02: Armar pedido con cantidades

**Como** cliente,  
**quiero** seleccionar varios platos y especificar cantidades,  
**para** armar mi pedido completo.

**Criterios de Aceptación:**
1. Dado que estoy viendo el menú, cuando selecciono un plato, entonces se abre un selector de cantidad.
2. Dado que seleccioné una cantidad, cuando la confirmo, entonces el plato se agrega a mi carrito.
3. Dado que tengo platos en mi carrito, cuando selecciono otro plato, entonces se agrega sin perder lo que ya tengo.
4. Dado que solicito una cantidad mayor al stock disponible, entonces el bot muestra un mensaje de error y no agrega el plato.

---

#### HU-C-03: Visualizar y modificar carrito

**Como** cliente,  
**quiero** ver el contenido de mi carrito y poder modificarlo,  
**para** asegurarme de que mi pedido es correcto antes de confirmarlo.

**Criterios de Aceptación:**
1. Dado que tengo platos en mi carrito, cuando elijo la opción "Ver carrito", entonces veo el detalle con platos, cantidades, subtotales y total.
2. Dado que estoy viendo el carrito, cuando elijo "Modificar cantidad" de un plato, entonces puedo aumentar o disminuir la cantidad.
3. Dado que estoy viendo el carrito, cuando elijo "Eliminar" de un plato, entonces desaparece del carrito.
4. Dado que vacío el carrito, cuando confirmo, entonces el carrito queda vacío y el total es 0.

---

#### HU-C-04: Enviar ubicación de entrega

**Como** cliente,  
**quiero** compartir mi ubicación mediante Telegram,  
**para** que el repartidor sepa dónde entregar mi pedido.

**Criterios de Aceptación:**
1. Dado que he confirmado mi pedido, cuando el bot me solicita la ubicación, entonces puedo enviar mi ubicación actual usando el adjunto de Telegram.
2. Dado que envío mi ubicación, cuando el bot la recibe, entonces se almacena latitud y longitud asociadas al pedido.
3. Dado que envío mi ubicación, cuando se guarda, entonces aparece en el panel de administración en el mapa.
4. Dado que estoy en el paso de ubicación, cuando envío un texto en lugar de ubicación, entonces el bot me pide que envíe una ubicación válida.

---

#### HU-C-05: Recibir QR de pago y enviar comprobante

**Como** cliente,  
**quiero** recibir un código QR para pagar y enviar el comprobante,  
**para** completar el pago de mi pedido.

**Criterios de Aceptación:**
1. Dado que envié mi ubicación, cuando el bot confirma, entonces recibo una imagen con el código QR de pago.
2. Dado que recibí el QR, cuando pago y envío una foto del comprobante, entonces el bot recibe la imagen y la almacena.
3. Dado que envío el comprobante, cuando se almacena, entonces aparece en el panel de administración asociado a mi pedido.
4. Dado que el administrador confirma el pago en el panel, cuando lo hace, entonces el estado del pedido cambia y recibo una notificación.

---

#### HU-C-06: Seguir estado del pedido

**Como** cliente,  
**quiero** recibir notificaciones automáticas del estado de mi pedido,  
**para** saber cuándo está listo, en camino y entregado.

**Criterios de Aceptación:**
1. Dado que mi pedido fue confirmado, cuando recibo el código de seguimiento, entonces sé que puedo usar ese código para consultar el estado.
2. Dado que el administrador cambia el estado de mi pedido en el panel, cuando lo hace, entonces recibo una notificación en Telegram.
3. Dado que el repartidor confirma la entrega, cuando lo hace, entonces recibo una notificación de "Pedido entregado".
4. Dado que mi pedido tiene un estado, cuando escribo `/estado [código]`, entonces el bot me responde con el estado actual.

---

#### HU-C-07: Cancelar pedido en curso

**Como** cliente,  
**quiero** poder cancelar mi pedido mientras está en proceso,  
**para** anularlo si cambié de opinión.

**Criterios de Aceptación:**
1. Dado que estoy en cualquier paso del flujo, cuando escribo `/cancelar`, entonces el bot cancela el pedido y limpia mi sesión.
2. Dado que el pedido ya fue confirmado y pagado, cuando solicito cancelar, entonces el bot indica que la cancelación debe ser gestionada por el administrador.
3. Dado que el administrador cancela desde el panel, cuando lo hace, entonces recibo una notificación de cancelación.

---

### 🟡 Épica 2: Bot - Delivery

#### HU-D-01: Autenticarse como repartidor

**Como** repartidor,  
**quiero** autenticarme en el bot mediante un código o registro previo,  
**para** recibir asignaciones de pedidos.

**Criterios de Aceptación:**
1. Dado que soy un repartidor registrado, cuando escribo `/start`, entonces el bot me pide mi código de autenticación.
2. Dado que ingreso un código válido, cuando el bot lo verifica, entonces veo los comandos de repartidor.
3. Dado que ingreso un código inválido, cuando el bot lo verifica, entonces muestra mensaje de error y me pide reintentar.
4. Dado que soy un repartidor autenticado, cuando vuelvo a iniciar el bot, entonces ya estoy autenticado y veo los comandos de repartidor.

---

#### HU-D-02: Recibir asignación de pedido

**Como** repartidor,  
**quiero** recibir la notificación de un pedido asignado,  
**para** conocer los detalles de la entrega.

**Criterios de Aceptación:**
1. Dado que estoy autenticado como repartidor, cuando el administrador me asigna un pedido desde el panel, entonces recibo un mensaje en Telegram con los detalles del pedido.
2. Dado que recibo la asignación, cuando abro el mensaje, entonces veo: número de pedido, platos, importe, estado del pago, dirección, ubicación en mapa y contacto del cliente.
3. Dado que recibo la asignación, cuando toco el botón "Acusar recibo", entonces el panel refleja que acepté el pedido.
4. Dado que el administrador reasigna un pedido de otro repartidor, cuando lo hace, entonces yo recibo la notificación y el otro repartidor deja de verlo.

---

#### HU-D-03: Enviar ubicación en tiempo real

**Como** repartidor,  
**quiero** compartir mi ubicación en vivo durante el trayecto,  
**para** que el cliente y el administrador sepan dónde estoy.

**Criterios de Aceptación:**
1. Dado que tengo un pedido asignado, cuando inicio el envío de ubicación en vivo, entonces el bot envía mi posición periódicamente.
2. Dado que estoy enviando ubicación en vivo, cuando el administrador ve el panel, entonces ve mi posición en el mapa.
3. Dado que pierdo señal, cuando el bot no recibe actualización, entonces en el panel se muestra la última ubicación registrada.
4. Dado que el cliente consulta el estado, cuando ve el seguimiento, entonces ve la ubicación del repartidor en el mapa.

---

#### HU-D-04: Confirmar llegada y entrega

**Como** repartidor,  
**quiero** confirmar cuando llego al destino y cuando entrego el pedido,  
**para** actualizar el estado del pedido.

**Criterios de Aceptación:**
1. Dado que estoy en el destino, cuando toco el botón "Llegué", entonces el estado cambia a "En destino" y el cliente recibe notificación.
2. Dado que entregué el pedido, cuando toco el botón "Entregado", entonces el estado cambia a "Entregado" y el cliente recibe notificación.
3. Dado que confirmo "Entregado", cuando lo hago, entonces el panel actualiza el estado y archiva el pedido.
4. Dado que confirmo "Entregado", cuando lo hago, entonces solicito una foto o código de verificación.

---
