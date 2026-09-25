# Especificación funcional y técnica --- Cotizador SET Logística v2

**Documento destinado a Claude / equipo de desarrollo**

**Objetivo:** definir la lógica completa del nuevo cotizador de SET
Logística, separando el motor de cálculo de la interfaz, la gestión de
clientes/acuerdos, la persistencia de cotizaciones y la integración
posterior con Transoft/Transoftware.

**Fuente principal de reglas:** documentación funcional y técnica del
cotizador SET vigente al 16/09/2026, más el esquema SQL
`cotizador_set.sql` entregado para el nuevo proyecto y la lista de APIs
de Transoft proporcionada para producción y testing.

> **Importante:** este documento define la lógica de negocio que debe
> implementarse. No se debe copiar ciegamente la arquitectura del
> cotizador anterior. La lógica existente sirve como referencia
> funcional; el nuevo proyecto debe tener un motor desacoplado,
> testeable y auditable.

------------------------------------------------------------------------

# 1. Objetivo general

El sistema será una aplicación Laravel para SET Logística con dos
grandes experiencias:

1.  **Cotizador público**
    -   No requiere cuenta.
    -   Utiliza la tarifa pública/estándar.
    -   Permite solicitar una cotización.
    -   Puede generar un lead.
    -   No permite acceder a información privada de clientes.
2.  **Cotizador autenticado**
    -   Disponible para usuarios creados manualmente por SET.
    -   No existe registro público de usuarios.
    -   El usuario pertenece a un `cliente`.
    -   Puede tener un `tipo_cliente` y un `acuerdo_comercial`.
    -   Puede obtener tarifas, descuentos, recargos y condiciones
        comerciales específicas.
    -   En etapas posteriores tendrá acceso a pedidos, documentos y
        seguimiento.

El motor de cotización debe ser **el mismo** para ambos casos. Lo que
cambia es el contexto comercial.

``` text
                 ┌───────────────────────┐
                 │ Solicitud de cotización│
                 └───────────┬───────────┘
                             │
                    ┌────────▼────────┐
                    │ Motor de cálculo│
                    └────────┬────────┘
                             │
            ┌────────────────┴────────────────┐
            │                                 │
     Usuario público                    Usuario cliente
            │                                 │
     Tarifa pública                    Acuerdo comercial
     Tipo PUBLICO                      Tipo B2B/B2B_PREMIUM
            │                                 │
            └────────────────┬────────────────┘
                             ▼
                    Resultado de cotización
```

------------------------------------------------------------------------

# 2. Alcance de este documento

## Incluido

-   Flujo funcional del cotizador.
-   Datos de entrada.
-   Origen y destino.
-   Retiro y entrega.
-   Bultos.
-   Pallets.
-   Peso.
-   Volumen.
-   Aforo.
-   Tramos logísticos.
-   Tarifas.
-   Escalones.
-   Puerta a puerta.
-   Mínimos.
-   Acuerdos comerciales.
-   Descuentos.
-   Recargos.
-   Margen.
-   Seguro.
-   Carga y descarga.
-   Tiempo estimado.
-   Manejo de rutas sin tarifa.
-   Persistencia.
-   Versionado del cálculo.
-   Auditoría.
-   Casos de prueba.
-   Separación del motor respecto de Transoft.
-   Uso de APIs Transoft relevantes para pedidos y seguimiento.

## Fuera del MVP del motor

No implementar dentro del motor:

-   Dashboard completo.
-   Gestión completa de usuarios.
-   Documentación fiscal.
-   Gestión completa de pedidos.
-   WhatsApp.
-   Email transaccional.
-   Integraciones externas de clientes.
-   Pagos.
-   Gestión de depósitos.
-   Ruteo GPS.
-   Viajes de chofer.

Esas funciones pueden integrarse después.

------------------------------------------------------------------------

# 3. Principios de arquitectura

## 3.1 El precio no debe calcularse en el Controller

No implementar toda la lógica dentro de:

``` text
CotizacionController
```

El Controller debe recibir la solicitud, validarla y delegar al motor.

Arquitectura recomendada:

``` text
HTTP Controller
      │
      ▼
CotizacionRequest
      │
      ▼
CotizadorService
      │
      ├── RutaResolver
      ├── TarifaResolver
      ├── AforoService
      ├── EscalonResolver
      ├── AcuerdoComercialService
      ├── MargenService
      ├── MinimoService
      ├── AdicionalesService
      └── TiempoEntregaService
      │
      ▼
ResultadoCotizacion
      │
      ├── persistencia
      └── respuesta frontend
```

## 3.2 El motor debe ser determinista

Para una misma entrada y las mismas tarifas/acuerdos vigentes debe
producir el mismo resultado.

No depender de:

-   datos visuales del frontend;
-   cálculos JavaScript como fuente de verdad;
-   Transoft;
-   estado temporal de otro servicio;
-   valores hardcodeados.

## 3.3 La cotización histórica debe ser reproducible

Nunca guardar únicamente:

``` text
total_final = 150000
```

También debe guardarse el detalle necesario para saber cómo se llegó a
ese valor.

La base nueva ya contempla `cotizacion_resultados`, con costos por
tramo, margen, descuento, IVA, seguro, adicionales, tiempos y
`version_algoritmo`.

------------------------------------------------------------------------

# 4. Concepto de contexto comercial

Toda cotización tiene un contexto:

``` text
PUBLICA
PANEL_CLIENTE
BACKOFFICE
API
```

La tabla `origenes_cotizacion` ya contempla:

    ID Código          Uso
  ---- --------------- ------------------------------
     1 WEB_PUBLICA     Cotizador público
     2 PANEL_CLIENTE   Cotizador autenticado
     3 BACKOFFICE      Cotización realizada por SET
     4 API             Integración

La cotización además tiene:

-   `tipo_cliente_id`
-   `cliente_id`
-   `usuario_id`
-   `acuerdo_id`

Estos valores deben funcionar como snapshot histórico.

## Público

``` text
tipo_cliente = PUBLICO
cliente_id = NULL
usuario_id = NULL
acuerdo_id = NULL
```

## Cliente autenticado

``` text
tipo_cliente = B2B / B2B_PREMIUM
cliente_id = cliente autenticado
usuario_id = usuario autenticado
acuerdo_id = acuerdo vigente aplicado
```

Nunca asumir que estar logueado implica automáticamente tener descuento.
El acuerdo debe existir y estar vigente.

------------------------------------------------------------------------

# 5. Usuarios y clientes

Los usuarios no se registran públicamente.

La tabla `usuarios` contempla:

-   rol;
-   cliente;
-   nombre;
-   email;
-   contraseña;
-   teléfono;
-   activo;
-   último acceso;
-   auditoría;
-   baja lógica.

Roles actuales:

``` text
ADMIN
CLIENTE
```

Un usuario CLIENTE debe estar asociado a un `cliente_id`.

El cliente tiene:

-   tipo de cliente;
-   estado;
-   razón social;
-   nombre de fantasía;
-   CUIT;
-   email;
-   teléfono;
-   dirección;
-   localidad.

La identidad del usuario nunca debe ser usada para introducir descuentos
hardcodeados.

------------------------------------------------------------------------

# 6. Tipos de cliente

La base contiene:

## PUBLICO

-   nivel 0;
-   no requiere usuario;
-   no permite acuerdo comercial;
-   descuento base 0%.

## B2B

-   nivel 1;
-   requiere usuario;
-   permite acuerdo comercial;
-   descuento base configurable.

## B2B_PREMIUM

-   nivel 2;
-   requiere usuario;
-   permite acuerdo comercial;
-   actualmente tiene descuento base de ejemplo del 5%.

> Los valores actuales del SQL son datos iniciales de
> ejemplo/configuración, no deben considerarse una regla universal de
> negocio si SET los modifica.

------------------------------------------------------------------------

# 7. Flujo del cotizador

## Paso 1 --- Origen

Solicitar:

-   provincia;
-   localidad;
-   modalidad de origen.

Opciones:

``` text
Entrego en sucursal
Necesito retiro en domicilio
```

Si se selecciona retiro:

``` text
solicita_retiro = true
```

y debe existir localidad de origen.

## Paso 2 --- Destino

Solicitar:

-   provincia;
-   localidad;
-   modalidad de recepción.

Opciones:

``` text
Retiro en sucursal
Entrega a domicilio
```

Si se selecciona retiro en sucursal:

``` text
retira_en_sucursal = true
solicita_entrega = false
```

Si se solicita entrega:

``` text
solicita_entrega = true
retira_en_sucursal = false
```

Estas opciones no deben permitir combinaciones incoherentes.

------------------------------------------------------------------------

# 8. Tramos logísticos

El envío se divide conceptualmente en tres tramos:

``` text
PRIMERA MILLA
origen del cliente
      ↓
depósito SET de origen

TRONCAL
depósito SET origen
      ↓
depósito SET destino

ÚLTIMA MILLA
depósito SET destino
      ↓
domicilio del destinatario
```

El troncal es obligatorio para el esquema estándar.

Primera y última milla son opcionales.

## Caso mínimo

Cliente entrega en sucursal y retira en sucursal:

``` text
TRONCAL
```

## Retiro + retiro en sucursal

``` text
PRIMERA MILLA + TRONCAL
```

## Entrega en domicilio sin retiro

``` text
TRONCAL + ÚLTIMA MILLA
```

## Retiro + entrega

``` text
PRIMERA MILLA + TRONCAL + ÚLTIMA MILLA
```

------------------------------------------------------------------------

# 9. Puerta a puerta

Existe un cuarto concepto:

``` text
PUERTA_A_PUERTA
```

Es una tarifa directa para una ruta/localidad específica.

Cuando existe, debe compararse contra la combinación:

``` text
TRONCAL + ÚLTIMA_MILLA
```

y elegirse el camino de menor costo.

La primera milla, si fue solicitada, se suma por separado.

Conceptualmente:

``` text
opción A:
puerta_puerta

opción B:
troncal + última_milla

costo_destino = MIN(opción A, opción B)

si solicita retiro:
costo_total += primera_milla
```

No comparar puerta a puerta contra una ruta que no corresponde.

------------------------------------------------------------------------

# 10. Resolución de tarifas

La tabla `tarifas` contiene:

``` text
proveedor_id
provincia_origen_id
provincia_destino_id
localidad_destino_id
tipo_servicio_id
unidad_medida_id
costo_unitario
maximo
vigente_desde
vigente_hasta
```

Una tarifa es válida si:

-   no está eliminada;
-   está vigente en la fecha de cálculo;
-   corresponde al proveedor/configuración utilizada;
-   corresponde al servicio;
-   corresponde al origen;
-   corresponde al destino;
-   corresponde a la localidad cuando la tarifa es local.

------------------------------------------------------------------------

# 11. Granularidad de las tarifas

## Troncal

Se busca por:

``` text
provincia origen → provincia destino
```

La localidad normalmente no forma parte de la búsqueda del troncal.

## Primera milla

Se busca por localidad de origen.

La estructura actual de tarifas reutiliza `localidad_destino_id` para
representar la localidad de primera milla dentro de la misma provincia.
Esto es una particularidad del modelo y debe encapsularse en
`TarifaResolver`, no exponerse al frontend.

## Última milla

Se intenta:

``` text
localidad destino
```

y, si el modelo/regla permite fallback:

``` text
provincia destino
```

La documentación del sistema anterior establece que la última milla
puede buscar localidad y luego provincia.

## Importante

No inventar una tarifa provincial si no existe.

------------------------------------------------------------------------

# 12. Unidades

La base contiene:

``` text
KG
M3
PALLET
BULTO
VIAJE
KM
```

La lógica vigente del cotizador de SET está basada principalmente en:

``` text
kg
m3
pallet
```

Las unidades adicionales del SQL (`BULTO`, `VIAJE`, `KM`) existen como
modelo extensible, pero no deben introducirse automáticamente en el
algoritmo actual sin una regla de negocio definida.

------------------------------------------------------------------------

# 13. Conversión de medidas

El usuario carga:

``` text
largo_cm
ancho_cm
alto_cm
peso_kg
cantidad
```

Volumen:

``` text
volumen_m3 =
(largo_cm / 100)
× (ancho_cm / 100)
× (alto_cm / 100)
× cantidad
```

Peso total:

``` text
peso_total_kg =
peso_kg × cantidad
```

Toneladas:

``` text
toneladas =
peso_total_kg / 1000
```

La tabla `cotizacion_bultos` ya tiene columnas generadas para volumen y
peso total.

------------------------------------------------------------------------

# 14. Palletización

La tabla `cotizacion_bultos` tiene:

``` text
pallets_equivalentes
```

pero el cálculo debe venir del motor y no de un valor enviado por el
cliente.

La regla vigente del cotizador anterior considera una posición
equivalente de pallet y redondea hacia arriba cuando corresponde.

Sin embargo, el algoritmo exacto de estiba debe quedar encapsulado en:

``` text
PalletEquivalenceService
```

para poder modificarlo sin alterar el resto del motor.

Nunca confiar en:

``` text
pallets_equivalentes
```

enviado desde el navegador.

------------------------------------------------------------------------

# 15. Regla de elección de unidad

Esta es una regla crítica.

## Carga efectivamente palletizada

Si:

``` text
tipo de carga = palletizada
```

y existe tarifa PALLET:

``` text
cantidad_pallets × precio_pallet
```

No comparar contra m³ ni tonelada.

## Carga no palletizada

Calcular:

``` text
costo_m3 = m3 × tarifa_m3
costo_tn = toneladas × tarifa_tn
```

y elegir:

``` text
MAX(costo_m3, costo_tn)
```

si ambas existen.

Si solamente existe una:

``` text
usar la disponible
```

La tarifa PALLET no entra en la comparación de una carga no palletizada,
salvo que la política comercial específicamente defina que es la única
alternativa disponible.

------------------------------------------------------------------------

# 16. Escalones de tarifa

Una misma ruta puede tener varias tarifas para una misma unidad.

Ejemplo:

``` text
hasta 4 pallets → $223.083 por pallet
5 o más         → $273.994 por pallet
```

En base:

``` text
maximo = 4
```

y:

``` text
maximo = NULL
```

para el escalón abierto.

Regla:

1.  Buscar todos los escalones válidos.
2.  Si el valor está dentro de un `maximo`, usar ese escalón.
3.  Si supera todos los máximos y existe un escalón sin máximo, usarlo.
4.  El precio es unitario.
5.  Multiplicar posteriormente por la cantidad/unidad correspondiente.

Nunca interpretar `costo_unitario` como precio total de la fila.

------------------------------------------------------------------------

# 17. Múltiples bultos

Cada bulto debe cotizarse individualmente.

Ejemplo:

``` text
Bulto A
1 pallet

Bulto B
3 cajas
```

No convertir automáticamente todo el envío en un único bulto.

Conceptualmente:

``` text
costo_total_bultos =
costo_bulto_1
+
costo_bulto_2
+
...
```

Esto es importante porque los escalones y la unidad de cobro pueden
cambiar según cada bulto.

La documentación del sistema anterior especifica que cada bulto se
cotiza como si viajara solo y luego se suman los precios.

------------------------------------------------------------------------

# 18. Mínimo por ruta

La tabla:

``` text
tarifas_minimas
```

permite definir:

``` text
provincia_origen
provincia_destino
localidad_destino
tipo_servicio
monto_minimo
```

El mínimo debe aplicarse al componente/ruta correspondiente según la
definición comercial.

Ejemplo conceptual:

``` text
subtotal_ruta = 42.000
minimo_ruta = 60.000

subtotal_ruta_aplicado = 60.000
```

No debe aplicarse el mínimo indiscriminadamente sobre:

``` text
seguro
carga
descarga
```

salvo que SET defina explícitamente lo contrario.

------------------------------------------------------------------------

# 19. Piso por bulto

La documentación anterior contempla un piso específico para determinados
tipos de bulto, especialmente caja y sobre.

En el nuevo proyecto no hardcodear:

``` text
if caja...
```

sin una configuración.

Debe existir una política configurable o una tabla de configuración que
determine:

``` text
tipo_bulto
piso
activo
vigencia
```

Si el proyecto decide reutilizar `costos_adicionales`, documentar
explícitamente la semántica.

------------------------------------------------------------------------

# 20. Acuerdos comerciales

La tabla:

``` text
acuerdos_comerciales
```

contiene:

``` text
cliente_id
codigo
nombre
descuento_porcentaje
recargo_porcentaje
vigente_desde
vigente_hasta
observaciones
```

Un acuerdo solamente puede aplicarse si:

``` text
cliente_id coincide
AND
fecha >= vigente_desde
AND
(vigente_hasta IS NULL OR fecha <= vigente_hasta)
AND
deleted_at IS NULL
```

------------------------------------------------------------------------

# 21. Condiciones comerciales

La tabla:

``` text
acuerdo_condiciones
```

permite condiciones por:

-   número;
-   texto;
-   booleano;
-   zona;
-   servicio.

Tipos actuales:

``` text
MINIMO_ENVIOS
VOLUMEN_MINIMO
PLAZO_PAGO
ZONA_COBERTURA
TIPO_CARGA
SERVICIO_INCLUIDO
RETIRO_DOMICILIO
ENTREGA_DOMICILIO
SEGURO_INCLUIDO
```

Estas condiciones no deben ser tratadas todas como descuentos.

Cada una tiene semántica propia.

Ejemplos:

``` text
RETIRO_DOMICILIO = true
```

puede significar que el retiro está incluido.

``` text
SEGURO_INCLUIDO = true
```

puede significar que el seguro no se cobra al cliente.

``` text
SERVICIO_INCLUIDO = X
```

puede eliminar o compensar un costo específico.

Antes de implementar una condición nueva debe definirse su efecto exacto
sobre el cálculo.

------------------------------------------------------------------------

# 22. Descuento de acuerdo

Si un acuerdo tiene:

``` text
descuento_porcentaje = 15
```

no significa que todas las tarifas individuales deban alterarse
permanentemente.

El descuento debe aplicarse durante el cálculo:

``` text
importe_con_descuento =
subtotal_sujeto × (1 - descuento / 100)
```

Guardar:

``` text
descuento_porcentaje
descuento_monto
```

como snapshot en `cotizacion_resultados`.

------------------------------------------------------------------------

# 23. Recargo de acuerdo

Si:

``` text
recargo_porcentaje = 10
```

aplicar sobre la base que determine la política comercial.

No asumir que siempre se aplica sobre el total final.

Debe existir una única definición en `AcuerdoComercialService`.

Recomendación:

``` text
base_comercial
↓
descuento
↓
recargo
```

pero debe quedar documentado como regla configurable.

------------------------------------------------------------------------

# 24. Margen de ganancia

La tabla:

``` text
margenes_ganancia
```

permite definir márgenes por:

-   tipo de cliente;
-   tipo de servicio;
-   vigencia.

El margen es:

``` text
costo × (1 + porcentaje / 100)
```

Ejemplo:

``` text
costo = 100.000
margen = 30%

precio con margen = 130.000
```

Pero existe una consideración fundamental:

**La documentación operativa del tarifario vigente indica que las
tarifas cargadas en el Excel son el precio final que SET cobra al
cliente y que el sistema no debe sumar nuevamente ganancia ni IVA.**

Por lo tanto:

-   no aplicar margen automáticamente sobre tarifas que ya sean precio
    final;
-   la tabla `margenes_ganancia` del nuevo SQL representa una capacidad
    futura/configurable;
-   su uso debe quedar detrás de una política explícita;
-   nunca sumar el 30% de ejemplo automáticamente solo porque exista el
    registro.

Esta distinción debe quedar clara en el código.

------------------------------------------------------------------------

# 25. IVA

La documentación vigente indica que el precio de la planilla ya es
precio final y el IVA está incluido.

Por lo tanto, en el esquema actual:

``` text
IVA agregado por el motor = 0
```

salvo que SET cambie formalmente la política.

La columna:

``` text
cotizacion_resultados.iva
```

debe conservar el valor aplicado como snapshot.

No hacer:

``` text
total × 1.21
```

automáticamente.

------------------------------------------------------------------------

# 26. Seguro

El seguro depende del valor declarado:

``` text
valor_declarado
```

y de un porcentaje configurable.

La documentación actual indica como ejemplo operativo:

``` text
0,8%
```

Pero el valor debe salir de configuración, no de un literal en código.

Fórmula conceptual:

``` text
seguro =
valor_declarado × porcentaje / 100
```

Si un acuerdo comercial contempla:

``` text
SEGURO_INCLUIDO = true
```

el servicio comercial debe determinar si el seguro se bonifica.

El resultado debe guardar:

``` text
costo_seguro
```

------------------------------------------------------------------------

# 27. Carga y descarga

Los costos adicionales se administran mediante:

``` text
costos_adicionales
```

Campos:

``` text
nombre
monto
unidad
activo
```

La unidad puede ser:

``` text
$
%
```

La configuración actual documentada contempla como ejemplo:

``` text
carga = $12.500
descarga = $12.500
```

Pero estos valores no deben estar hardcodeados.

Si el cliente selecciona carga:

``` text
costo += precio_carga
```

Si selecciona descarga:

``` text
costo += precio_descarga
```

Si selecciona ambas:

``` text
costo += precio_carga + precio_descarga
```

El snapshot debe almacenarse en:

``` text
cotizacion_costos_adicionales
```

------------------------------------------------------------------------

# 28. Orden recomendado del cálculo

El motor debe tener un pipeline explícito.

``` text
1. Validar entrada
2. Determinar contexto comercial
3. Determinar tipo de cliente
4. Resolver acuerdo vigente
5. Resolver origen/destino
6. Resolver modalidad de retiro/entrega
7. Calcular dimensiones de cada bulto
8. Calcular peso
9. Calcular volumen
10. Calcular equivalencia de pallet
11. Resolver tarifas de cada tramo
12. Resolver escalones
13. Determinar unidad de cobro
14. Calcular cada bulto
15. Sumar bultos por tramo
16. Comparar puerta a puerta vs troncal + última milla cuando corresponda
17. Aplicar primera milla cuando corresponda
18. Aplicar mínimos
19. Aplicar política comercial/acuerdo
20. Aplicar margen si la política vigente lo requiere
21. Aplicar seguro
22. Aplicar carga/descarga
23. Aplicar IVA solamente si la política vigente lo requiere
24. Calcular tiempo estimado
25. Redondear según política monetaria
26. Generar resultado auditable
27. Persistir cotización
28. Devolver resultado
```

El orden exacto de descuento/margen/adicionales debe ser una política
centralizada y no implementarse dispersamente.

------------------------------------------------------------------------

# 29. No mezclar costo logístico con precio comercial

Conceptualmente separar:

``` text
Costo logístico
    ↓
Subtotal de flete
    ↓
Reglas comerciales
    ↓
Seguro
    ↓
Servicios adicionales
    ↓
Precio final
```

Esto permite que un cliente B2B pueda tener condiciones distintas sin
duplicar el motor logístico.

------------------------------------------------------------------------

# 30. Tiempo estimado

La tabla:

``` text
tiempos_estimados
```

contiene:

``` text
provincia_origen_id
provincia_destino_id
localidad_origen_id
localidad_destino_id
dias_min
dias_max
observacion
```

Debe buscar primero la combinación más específica disponible.

Preferencia recomendada:

``` text
origen localidad + destino localidad
        ↓
origen localidad + destino provincia
        ↓
origen provincia + destino localidad
        ↓
origen provincia + destino provincia
```

siempre que esas filas estén contempladas por la política de SET.

Si no existe:

``` text
tiempo_estimado = null
```

y frontend:

``` text
A confirmar
```

No inventar días.

------------------------------------------------------------------------

# 31. Ruta sin tarifa

No debe considerarse un error técnico.

Ejemplo:

``` text
No existe tarifa para:
Reconquista → determinada localidad
```

Respuesta:

``` text
ATENCION_PERSONALIZADA
```

El sistema debe guardar la solicitud.

Información útil:

``` text
origen
destino
localidad
bultos
peso
volumen
servicios
tipo_cliente
cliente
fecha
```

Esto permite construir posteriormente un radar de rutas solicitadas sin
precio.

------------------------------------------------------------------------

# 32. Estados internos de una cotización

El esquema debe permitir que una cotización tenga estados configurables.

Como mínimo conceptualmente:

``` text
BORRADOR
CALCULADA
SOLICITADA
EN_REVISION
RESPONDIDA
ACEPTADA
RECHAZADA
VENCIDA
CONVERTIDA_PEDIDO
```

Los nombres definitivos deben coincidir con los catálogos del proyecto.

------------------------------------------------------------------------

# 33. Persistencia de la cotización

Una cotización debe guardar:

## Cabecera

``` text
codigo
origen_id
tipo_cliente_id
cliente_id
usuario_id
acuerdo_id
estado_id
pedido_id
```

## Envío

``` text
provincia_origen_id
localidad_origen_id
provincia_destino_id
localidad_destino_id
solicita_retiro
solicita_entrega
retira_en_sucursal
valor_declarado
dias_almacenamiento
```

## Bultos

``` text
tipo_bulto_id
largo_cm
ancho_cm
alto_cm
peso_kg
cantidad
volumen_m3
peso_total_kg
pallets_equivalentes
costo_individual
```

## Resultado

``` text
costo_troncal
costo_primera_milla
costo_ultima_milla
costo_puerta_puerta
subtotal_flete
costo_seguro
costo_carga_descarga
incluye_carga_descarga
margen_ganancia
descuento_porcentaje
descuento_monto
iva
total_final
tiempo_estimado_min
tiempo_estimado_max
version_algoritmo
```

------------------------------------------------------------------------

# 34. Snapshot obligatorio

Una cotización histórica no debe cambiar porque mañana SET modifique:

-   una tarifa;
-   un acuerdo;
-   un porcentaje de seguro;
-   un costo adicional;
-   un margen;
-   un tiempo estimado.

Por eso deben guardarse snapshots.

Por ejemplo:

``` text
cotizacion_resultados.descuento_porcentaje
cotizacion_resultados.descuento_monto
cotizacion_resultados.costo_seguro
cotizacion_costos_adicionales.monto_aplicado
cotizacion_bultos.costo_individual
```

Además:

``` text
version_algoritmo = "v2.0"
```

------------------------------------------------------------------------

# 35. Código de cotización

La tabla:

``` text
cotizacion_codigos
```

permite asociar un código público a una cotización.

El código no debe revelar:

-   ID incremental;
-   cliente;
-   usuario;
-   información interna.

Ejemplo:

``` text
SET-2026-8F4K2
```

o equivalente.

Debe ser único.

------------------------------------------------------------------------

# 36. Redondeo monetario

Toda operación interna debe evitar errores de coma flotante.

En PHP:

-   usar `decimal`/strings o una estrategia decimal segura;
-   no confiar en `float` para el precio final.

El redondeo monetario debe hacerse en un punto definido del pipeline.

Recomendación:

``` text
cálculo interno
↓
redondeo por componente cuando la política lo exija
↓
subtotal
↓
total final
```

No redondear aleatoriamente en cada operación.

------------------------------------------------------------------------

# 37. Frontend recomendado

El frontend debe ser un wizard:

``` text
1. Origen
2. Destino
3. Mercadería
4. Servicios
5. Datos de contacto
6. Resultado
```

No mostrar campos que no correspondan.

Ejemplo:

Si:

``` text
retira_en_sucursal = true
```

no mostrar opciones de última milla.

Si:

``` text
solicita_retiro = false
```

no pedir dirección de retiro.

------------------------------------------------------------------------

# 38. Resultado para usuario público

Mostrar:

``` text
Origen
Destino

Detalle del transporte
Detalle de servicios
Seguro
Tiempo estimado

TOTAL
```

Además:

``` text
Código de cotización
```

y opción:

``` text
Solicitar esta cotización
Enviar por email
Descargar PDF
```

Las funciones de email/PDF pueden implementarse después.

------------------------------------------------------------------------

# 39. Resultado para cliente autenticado

Mostrar adicionalmente:

``` text
Cliente
Acuerdo aplicado
Descuento aplicado
Condiciones comerciales relevantes
```

No mostrar información interna como:

-   margen interno;
-   costo de SET;
-   IDs de tarifas;
-   proveedor;
-   reglas internas.

El usuario cliente debe ver su precio comercial, no la estructura
interna de costos.

------------------------------------------------------------------------

# 40. Diferencia entre tarifa pública y tarifa cliente

No duplicar tablas de tarifas.

Preferir:

``` text
tarifas base
      +
contexto comercial
      +
acuerdo
      +
condiciones
```

Así:

``` text
Tarifa pública = tarifa base
Tarifa B2B = tarifa base + reglas comerciales
Tarifa Premium = tarifa base + reglas premium
```

Si SET necesita una tarifa completamente distinta para un cliente, debe
existir una decisión explícita de modelo antes de agregar excepciones.

------------------------------------------------------------------------

# 41. Transoft / Transoftware

La API proporcionada pertenece al sistema de transporte externo y debe
tratarse como una integración separada.

**No es el motor de precios.**

Su responsabilidad principal para este proyecto será:

``` text
pedido
 ↓
carga
 ↓
tracking
 ↓
estados
 ↓
evidencia
 ↓
seguimiento
```

------------------------------------------------------------------------

# 42. APIs Transoft relevantes para el proyecto

## Alta de cargas --- legacy

``` http
POST /api/v3/cargas/add/{username}/{operationId}
```

Genera una carga.

## Modificación legacy

``` http
PUT /api/cargas/edit/{tracking}/{username}/{operationId}
```

## Consulta legacy

``` http
GET /api/cargas/v2/{username}/{operationId}/{tracking}
```

Versión extendida.

``` http
GET /api/cargas/{username}/{operationId}/{tracking}
```

Consulta básica.

## Buscar por documento

``` http
GET /api/cargas/find/document/{username}/{operationId}/{documentNumber}
```

------------------------------------------------------------------------

# 43. Transoft v4 --- API preferida para diseño nuevo

Cuando sea posible, priorizar el contrato v4 frente al legacy.

## Obtener carga

``` http
GET /api/v4/cargas/{tracking}
```

## Modificar carga

``` http
PUT /api/v4/cargas/{tracking}
```

## Listar cargas

``` http
GET /api/v4/cargas
```

## Crear carga

``` http
POST /api/v4/cargas
```

La API indica que:

-   un rol Dador recibe estado PC (Precarga) forzado por autorización;
-   un Transportista sigue el pipeline de alta.

La aplicación SET debe respetar la autorización que determine el
servidor.

## Buscar por documento

``` http
GET /api/v4/cargas/document/{documentNumber}
```

------------------------------------------------------------------------

# 44. Estados Transoft

## Historial

``` http
GET /api/v4/cargas/{tracking}/states
```

Devuelve el historial cronológico de estados de una carga.

## Registrar Conforme

``` http
POST /api/v4/cargas/{tracking}/states
```

El servidor determina la rama del circuito.

La operación es idempotente para repetir el mismo estado.

Para el portal SET, la consulta de estados es mucho más importante que
registrar estados desde el cotizador.

------------------------------------------------------------------------

# 45. Evidencia de entrega

## Imágenes

``` http
GET /api/v4/cargas/{tracking}/images
```

## Agregar imagen

``` http
POST /api/v4/cargas/{tracking}/images
```

## Firma

``` http
GET /api/v4/cargas/{tracking}/signature
```

## Reemplazar firma

``` http
PUT /api/v4/cargas/{tracking}/signature
```

Estas funciones pertenecen al módulo posterior de pedidos/seguimiento,
no al cálculo de la cotización.

------------------------------------------------------------------------

# 46. Precargas

Legacy:

``` http
POST /api/precargas/v3/add/{username}/{operationId}
```

V4 no fue listada como endpoint específico de creación de precargas en
la documentación proporcionada.

Consulta:

``` http
GET /api/precargas/{username}/{operationId}/{tracking}
```

El endpoint:

``` text
/api/precargas/get/...
```

está marcado como obsoleto.

------------------------------------------------------------------------

# 47. Webhooks

Transoft permite suscribir el transportista a eventos de estados:

``` http
POST /api/v4/transportistas/suscripcion-webhook
```

Esto puede utilizarse posteriormente para evitar consultar
constantemente:

``` text
GET /api/v4/cargas/{tracking}/states
```

Arquitectura futura:

``` text
Transoft
   │
   │ webhook
   ▼
Laravel SET
   │
   ▼
seguimientos
   │
   ▼
portal cliente
```

El webhook debe procesarse de forma idempotente.

------------------------------------------------------------------------

# 48. Separación Cotización → Pedido → Transoft

Nunca hacer:

``` text
cotizar()
→ llamar Transoft
→ obtener precio
```

El flujo correcto es:

``` text
Cotización
    │
    ├── calcula precio localmente
    │
    ▼
Cliente acepta
    │
    ▼
Pedido SET
    │
    ▼
Alta/creación de carga Transoft
    │
    ▼
tracking
    │
    ▼
seguimiento
```

Esto desacopla el precio del sistema operativo de transporte.

------------------------------------------------------------------------

# 49. Datos Transoft en `pedidos`

El SQL ya contempla:

``` text
transoft_tracking
transoft_operation_id
transoft_estado_codigo
transoft_etiqueta_url
transoft_seguimiento_url
transoft_sync_at
transoft_payload_json
```

Esto es apropiado para conservar una referencia de la integración.

No guardar contraseñas ni tokens Transoft en texto plano.

------------------------------------------------------------------------

# 50. Credenciales Transoft

La API proporciona:

``` http
POST /api/v4/credenciales/{username}
```

La contraseña va en el cuerpo.

Las credenciales externas no deben guardarse en:

``` text
clientes
usuarios
cotizaciones
pedidos
```

en texto plano.

La tabla `cliente_integraciones` ya utiliza:

``` text
credenciales_ref
```

con la intención de guardar una referencia a un vault.

------------------------------------------------------------------------

# 51. Producción vs testing

La lista proporcionada muestra el mismo contrato de endpoints para:

``` text
producción
testing
```

La aplicación debe manejar la URL base mediante configuración:

``` env
TRANSOFT_BASE_URL=
TRANSOFT_USERNAME=
TRANSOFT_OPERATION_ID=
```

Nunca hardcodear una URL de producción en el código.

Ejemplo conceptual:

``` php
config('services.transoft.base_url')
```

------------------------------------------------------------------------

# 52. Cliente HTTP Transoft

Crear un servicio independiente:

``` text
TransoftClient
```

Responsabilidades:

``` text
getCarga()
crearCarga()
actualizarCarga()
buscarCargaPorDocumento()
obtenerEstados()
obtenerImagenes()
obtenerFirma()
crearRelacionComprobante()
```

No permitir que `CotizadorService` dependa de `TransoftClient`.

------------------------------------------------------------------------

# 53. Errores Transoft

Distinguir:

``` text
error de autenticación
error de autorización
404 carga inexistente
timeout
5xx Transoft
respuesta inválida
```

Una caída de Transoft no debe corromper una cotización.

Si falla al crear un pedido:

``` text
pedido local = pendiente_de_sincronizacion
```

y se debe permitir reintento.

------------------------------------------------------------------------

# 54. Idempotencia

Las operaciones de creación hacia Transoft deben diseñarse para evitar
cargas duplicadas.

Antes de crear:

``` text
¿pedido ya tiene tracking?
```

Si sí:

``` text
no volver a crear automáticamente
```

Si la API externa permite una clave idempotente, utilizarla.

------------------------------------------------------------------------

# 55. Auditoría

La tabla:

``` text
auditoria
```

es importante para el panel administrativo.

Debe registrar cambios sensibles como:

-   tarifas;
-   mínimos;
-   acuerdos;
-   usuarios;
-   clientes;
-   costos adicionales;
-   márgenes;
-   condiciones comerciales.

Nunca registrar:

-   contraseñas;
-   tokens;
-   credenciales externas.

La auditoría ya contempla:

``` text
tabla
registro_id
accion
usuario_id
usuario_nombre
usuario_bd
ip
contexto
campos_modificados
datos_anteriores
datos_nuevos
created_at
```

------------------------------------------------------------------------

# 56. Seguridad del cotizador público

El endpoint público debe:

-   validar todos los campos en backend;
-   limitar tamaño de request;
-   limitar cantidad de bultos;
-   limitar valores máximos;
-   evitar SQL generado desde inputs;
-   usar IDs existentes;
-   impedir seleccionar tarifas manualmente;
-   impedir enviar descuentos desde frontend;
-   impedir enviar `cliente_id` arbitrario;
-   impedir enviar `acuerdo_id` arbitrario;
-   impedir enviar `total_final`;
-   calcular todo nuevamente en backend.

El frontend nunca es una fuente confiable para el precio.

------------------------------------------------------------------------

# 57. Payload recomendado

Ejemplo conceptual:

``` json
{
  "origen": {
    "provincia_id": 5,
    "localidad_id": 123,
    "retiro": true
  },
  "destino": {
    "provincia_id": 6,
    "localidad_id": 456,
    "entrega": true
  },
  "bultos": [
    {
      "tipo_bulto_id": 1,
      "cantidad": 2,
      "largo_cm": 40,
      "ancho_cm": 30,
      "alto_cm": 20,
      "peso_kg": 5,
      "palletizado": false
    }
  ],
  "valor_declarado": 1000000,
  "servicios": {
    "carga": true,
    "descarga": false
  }
}
```

El backend debe transformar esto en DTOs internos.

------------------------------------------------------------------------

# 58. Resultado interno recomendado

``` json
{
  "estado": "CALCULADA",
  "cotizacion_codigo": "SET-XXXXXX",
  "moneda": "ARS",
  "tramos": {
    "primera_milla": 40000,
    "troncal": 85000,
    "ultima_milla": 44000,
    "puerta_puerta": 0
  },
  "subtotal_flete": 169000,
  "seguro": 8000,
  "carga_descarga": 12500,
  "descuento": 0,
  "iva": 0,
  "total": 189500,
  "tiempo": {
    "min": 3,
    "max": 5
  }
}
```

Los números son solamente ilustrativos.

------------------------------------------------------------------------

# 59. No devolver información interna

La API pública NO debe devolver:

``` text
proveedor_id
tarifa_id
costo interno
margen
SQL
acuerdo interno completo
reglas comerciales privadas
```

Debe devolver solamente información comercial necesaria.

------------------------------------------------------------------------

# 60. Casos de error funcional

## Falta origen

``` text
ORIGEN_REQUERIDO
```

## Falta destino

``` text
DESTINO_REQUERIDO
```

## Sin tarifa troncal

``` text
RUTA_SIN_TARIFA
```

## Sin primera milla

``` text
RETIRO_NO_DISPONIBLE
```

## Sin última milla

``` text
ENTREGA_NO_DISPONIBLE
```

## Datos de bulto inválidos

``` text
BULTO_INVALIDO
```

## Valor declarado inválido

``` text
VALOR_DECLARADO_INVALIDO
```

## Acuerdo vencido

No es error para el usuario: simplemente no se aplica el acuerdo.

------------------------------------------------------------------------

# 61. Regla para provincias sin depósito

La existencia de una provincia con depósito no debe ser utilizada como
única condición de validez de una ruta.

La documentación anterior indica que el motor debe depender de las
tarifas disponibles.

Por lo tanto:

``` text
tiene_deposito = dato operativo
```

pero:

``` text
tarifa existente = condición real de cotización
```

No implementar:

``` php
if (!$provincia->tiene_deposito) return error;
```

sin antes comprobar el modelo logístico.

------------------------------------------------------------------------

# 62. Almacenamiento

El modelo anterior contempla almacenamiento por días.

La documentación vigente indica que no hay tarifas cargadas y que SET
decidió no utilizarlo por ahora.

Por lo tanto:

``` text
dias_almacenamiento
```

puede permanecer en la estructura.

Pero el motor no debe cobrar almacenamiento hasta que exista una
tarifa/política activa.

Si se implementa posteriormente:

``` text
precio_dia × max(1, dias)
```

------------------------------------------------------------------------

# 63. Camión completo

Existe como servicio en el modelo anterior, pero no hay tarifas
operativas cargadas.

El SQL nuevo también tiene:

``` text
VIAJE
```

como unidad y:

``` text
EXPRESO
```

como servicio.

No activar estos servicios automáticamente.

Deben ser habilitados por configuración y tener reglas específicas.

------------------------------------------------------------------------

# 64. Extensibilidad

Para agregar un nuevo servicio:

``` text
TipoServicio
```

debe definir:

``` text
codigo
nombre
activo
```

y, si tiene lógica propia:

``` text
ServicioCalculator
```

Ejemplo:

``` text
AlmacenamientoCalculator
ExpresoCalculator
ViajeCompletoCalculator
```

No meter todos los `if` dentro del calculador principal.

------------------------------------------------------------------------

# 65. Servicio principal

Nombre recomendado:

``` text
CotizadorService
```

Entrada:

``` text
CotizacionRequestData
```

Salida:

``` text
ResultadoCotizacion
```

Pseudoestructura:

``` php
public function calcular(CotizacionRequestData $request): ResultadoCotizacion
{
    $contexto = $this->contextResolver->resolve($request);

    $ruta = $this->rutaResolver->resolve($request);

    $bultos = $this->aforoService->calcular($request->bultos);

    $costos = $this->routeCalculator->calculate(
        $ruta,
        $bultos,
        $contexto
    );

    $comercial = $this->acuerdoService->apply(
        $costos,
        $contexto
    );

    $adicionales = $this->adicionalesService->calculate(
        $request,
        $contexto
    );

    $tiempo = $this->tiempoService->resolve($ruta);

    return $this->resultBuilder->build(
        $costos,
        $comercial,
        $adicionales,
        $tiempo
    );
}
```

Es pseudocódigo. No copiar literalmente sin adaptar al proyecto.

------------------------------------------------------------------------

# 66. Reglas que NO deben hardcodearse

No hardcodear:

``` text
0.8% seguro
12500 carga
12500 descarga
30% margen
15% descuento
provincias
localidades
tarifas
mínimos
días de entrega
```

Todo lo que sea configurable debe venir de DB/configuración.

Las reglas estructurales del algoritmo sí pueden estar en código.

------------------------------------------------------------------------

# 67. Reglas que sí pertenecen al algoritmo

Pertenecen al código:

``` text
cómo convertir cm → m3
cómo convertir kg → tn
cómo elegir escalón
cómo elegir unidad
cómo comparar puerta a puerta
cómo dividir en tramos
cómo determinar qué tramo corresponde
cómo calcular cada bulto
cómo aplicar prioridades
```

Pertenecen a datos/configuración:

``` text
precio
mínimo
porcentaje
vigencia
servicio activo
tipo cliente
acuerdo
zona
```

------------------------------------------------------------------------

# 68. Prioridad de configuración comercial

Para evitar ambigüedad, definir una política central.

Propuesta:

``` text
1. tarifa base vigente
2. tipo de cliente
3. acuerdo comercial vigente
4. condiciones del acuerdo
5. descuento/recargo
6. margen si corresponde
7. adicionales
8. seguro
9. IVA según política fiscal
```

Pero esta prioridad debe estar encapsulada en:

``` text
CommercialPricingPolicy
```

y tener tests.

------------------------------------------------------------------------

# 69. Versionado

Cada resultado debe guardar:

``` text
version_algoritmo
```

Ejemplo:

``` text
v2.0.0
```

Si se modifica una regla crítica:

``` text
v2.1.0
```

Si se corrige un bug que puede cambiar precios:

``` text
v2.1.1
```

No modificar silenciosamente una regla histórica sin incrementar versión
cuando pueda cambiar resultados.

------------------------------------------------------------------------

# 70. Tests obligatorios

Crear tests unitarios para:

### Medidas

-   volumen;
-   peso total;
-   toneladas;
-   pallet equivalente.

### Tarifa

-   tarifa única;
-   múltiples unidades;
-   escalones;
-   escalón abierto;
-   tarifa vencida;
-   tarifa futura.

### Rutas

-   solo troncal;
-   primera + troncal;
-   troncal + última;
-   primera + troncal + última;
-   puerta a puerta más barato;
-   troncal + última más barato.

### Bultos

-   un bulto;
-   varios bultos;
-   cantidades diferentes;
-   tipos diferentes.

### Comercial

-   público;
-   B2B sin acuerdo;
-   B2B con acuerdo;
-   acuerdo vencido;
-   descuento;
-   recargo;
-   seguro incluido.

### Adicionales

-   carga;
-   descarga;
-   ambas;
-   ninguna.

### Mínimos

-   debajo del mínimo;
-   encima del mínimo.

### Errores

-   ruta sin tarifa;
-   localidad sin última milla;
-   retiro no disponible;
-   datos inválidos.

------------------------------------------------------------------------

# 71. Casos de referencia del sistema anterior

La documentación vigente del cotizador anterior verificó ejemplos
reales:

  Caso                                 Resultado documentado
  ---------------------------------- -----------------------
  1 pallet Córdoba → Buenos Aires                   \$73.285
  3 pallets Córdoba → Buenos Aires                 \$219.855
  1 pallet Córdoba → CABA                           \$86.900
  \+ entrega Avellaneda                            \$134.035
  \+ retiro Alta Gracia                             \$98.285
  Retiro Rosario → Córdoba                      \$125.263,57
  Córdoba → Tucumán                                \$212.790
  Seguro sobre \$1.000.000                          \$81.285
  Con carga y descarga                              \$98.285

Estos casos deben utilizarse como regresión solamente si las
tarifas/configuración del nuevo entorno son equivalentes.

No asumir que el SQL nuevo contiene exactamente los mismos tarifarios.

------------------------------------------------------------------------

# 72. Ejemplo de flujo completo

Supongamos:

``` text
Origen:
Córdoba

Destino:
Buenos Aires

Retiro:
No

Entrega:
Sí

Mercadería:
1 pallet

Peso:
700 kg

Cliente:
Público
```

El motor:

``` text
1. identifica ruta Córdoba → Buenos Aires
2. encuentra troncal
3. encuentra última milla si corresponde
4. detecta carga palletizada
5. busca tarifa PALLET
6. aplica escalón según cantidad/peso
7. calcula troncal
8. calcula última milla
9. compara puerta a puerta si existe
10. suma
11. aplica mínimos
12. seguro si corresponde
13. adicionales si corresponden
14. determina tiempo
15. guarda snapshot
```

------------------------------------------------------------------------

# 73. Ejemplo cliente B2B

Misma operación:

``` text
cliente_id = 1
tipo_cliente = B2B
```

El motor encuentra:

``` text
acuerdo B2B_MUEBLES_01
descuento = 15%
```

Entonces:

``` text
tarifa base
↓
cálculo logístico
↓
subtotal
↓
descuento comercial
↓
seguro/adicionales según condiciones
↓
total cliente
```

El descuento no modifica la tarifa almacenada.

------------------------------------------------------------------------

# 74. Qué hacer cuando el usuario modifica el formulario

El frontend puede recalcular visualmente, pero el backend debe ser
autoridad.

Opciones:

``` text
POST /cotizaciones/preview
```

para obtener una vista previa.

Luego:

``` text
POST /cotizaciones
```

para guardar.

El endpoint final debe recalcular desde cero.

Nunca aceptar:

``` json
{
  "total": 123456
}
```

como fuente de verdad.

------------------------------------------------------------------------

# 75. Cotización preview

Una buena arquitectura es:

``` text
POST /api/cotizador/calcular
```

No necesariamente guarda.

Devuelve:

``` text
ResultadoCotizacion
```

Después:

``` text
POST /api/cotizaciones
```

guarda la cotización calculándola nuevamente.

Esto evita que un precio manipulado en frontend termine persistido.

------------------------------------------------------------------------

# 76. Cache

Se puede cachear:

-   provincias;
-   localidades;
-   tipos de bulto;
-   servicios;
-   unidades;
-   tarifas vigentes.

No cachear ciegamente el precio final de un cliente si los acuerdos
pueden cambiar.

Si se usa cache para tarifas:

``` text
invalidar al modificar tarifa
```

------------------------------------------------------------------------

# 77. Concurrencia

Si dos administradores modifican una tarifa:

-   usar `updated_at`;
-   detectar conflictos cuando sea necesario;
-   registrar auditoría.

Una cotización ya calculada no debe cambiar retroactivamente.

------------------------------------------------------------------------

# 78. Vigencia

Toda tarifa debe respetar:

``` text
vigente_desde
vigente_hasta
```

Fecha:

``` text
fecha_calculo
```

Debe ser una fecha definida por el backend.

No aceptar una fecha arbitraria del cliente público para obtener tarifas
históricas.

El backoffice sí podría necesitar recalcular históricamente, pero eso
debe ser una capacidad administrativa explícita.

------------------------------------------------------------------------

# 79. Zona

La tabla `zonas` existe principalmente para condiciones comerciales.

Ejemplo:

``` text
NORTE
CENTRO
CUYO
SUR
AMBA
NEA
```

No utilizar zonas automáticamente para cambiar precios si no existe una
regla que lo indique.

Una zona de acuerdo puede restringir la aplicación del acuerdo.

------------------------------------------------------------------------

# 80. Integridad de datos

Antes de calcular:

``` text
provincia existe
localidad pertenece a provincia
tipo bulto activo
servicio activo
cliente activo
acuerdo pertenece al cliente
acuerdo vigente
```

No aceptar:

``` text
localidad de Santa Fe + provincia Córdoba
```

aunque los IDs existan individualmente.

------------------------------------------------------------------------

# 81. Recomendación sobre la UI

La experiencia debe ser:

``` text
rápida
simple
progresiva
responsive
```

Evitar un formulario de 30 campos.

El usuario debería entender en todo momento:

``` text
Dónde retiro
Dónde entrego
Qué envío
Cuánto ocupa
Qué servicios necesita
Cuánto cuesta
```

------------------------------------------------------------------------

# 82. Resumen de la lógica central

La lógica puede resumirse así:

``` text
ENTRADA
  ↓
Origen + destino
  ↓
Retiro / entrega
  ↓
Bultos
  ↓
Peso + volumen + pallet
  ↓
Resolver tramos
  ↓
Buscar tarifas
  ↓
Resolver escalones
  ↓
Elegir unidad
  ↓
Calcular cada bulto
  ↓
Sumar tramos
  ↓
Comparar puerta a puerta
  ↓
Aplicar mínimos
  ↓
Aplicar acuerdo
  ↓
Aplicar condiciones
  ↓
Seguro
  ↓
Carga / descarga
  ↓
IVA según política
  ↓
Tiempo estimado
  ↓
Snapshot
  ↓
RESULTADO
```

------------------------------------------------------------------------

# 83. Regla de oro para Claude

Claude debe tratar este documento como especificación de negocio y no
como una invitación a improvisar.

Antes de modificar la lógica:

1.  revisar las tablas existentes;
2.  revisar las relaciones;
3.  revisar las migraciones;
4.  revisar los modelos Laravel;
5.  revisar los datos reales;
6.  identificar qué reglas son configurables;
7.  escribir tests;
8.  recién después modificar código.

No eliminar tablas ni crear estructuras paralelas sin justificarlo.

No reemplazar el modelo existente por uno inventado.

------------------------------------------------------------------------

# 84. Regla de oro sobre Transoft

Transoft/Transoftware **no determina el precio de la cotización**.

El cotizador calcula localmente.

Transoft entra cuando existe un pedido/carga que debe ser gestionado por
transporte:

``` text
COTIZACIÓN
   ↓
ACEPTACIÓN
   ↓
PEDIDO
   ↓
TRANSOFT
   ↓
TRACKING
   ↓
ESTADOS
   ↓
EVIDENCIA
```

------------------------------------------------------------------------

# 85. Resultado esperado del desarrollo

El resultado final debe permitir:

### Público

``` text
Entrar
→ cotizar
→ obtener precio estándar
→ guardar cotización
→ recibir código
→ solicitar atención
```

### Cliente

``` text
Ingresar
→ cotizar
→ aplicar acuerdo comercial
→ obtener precio especial
→ consultar cotizaciones
→ posteriormente consultar pedidos/documentos/seguimiento
```

### SET

``` text
Administrar clientes
Administrar usuarios
Administrar tarifas
Administrar mínimos
Administrar acuerdos
Administrar condiciones
Administrar adicionales
Consultar cotizaciones
Convertir cotización en pedido
Sincronizar pedido con Transoft
Consultar tracking
Auditar cambios
```

------------------------------------------------------------------------

# 86. Criterio de aceptación del motor

El motor estará correctamente implementado cuando:

-   no dependa del frontend para calcular precios;
-   no dependa de Transoft para calcular precios;
-   permita cotizar público y cliente con el mismo pipeline;
-   permita múltiples bultos;
-   calcule volumen y peso correctamente;
-   diferencie palletizado de carga suelta;
-   respete escalones;
-   respete vigencias;
-   respete primera/troncal/última milla;
-   compare puerta a puerta;
-   aplique mínimos;
-   aplique acuerdos;
-   aplique condiciones comerciales;
-   calcule seguro configurable;
-   calcule adicionales configurables;
-   no agregue IVA/margen cuando las tarifas ya sean precio final;
-   guarde snapshots;
-   guarde versión de algoritmo;
-   sea reproducible;
-   tenga tests;
-   pueda evolucionar sin reescribir todo el sistema.

------------------------------------------------------------------------

# 87. Nota importante sobre inconsistencias entre documentación y nuevo SQL

Hay diferencias deliberadas entre el sistema documentado en septiembre y
el esquema SQL entregado para el nuevo proyecto.

Ejemplos:

-   el SQL nuevo tiene `margenes_ganancia`, mientras que la
    documentación operativa indica que el precio del Excel ya es final;
-   el SQL nuevo tiene unidades `BULTO`, `VIAJE` y `KM`;
-   el SQL nuevo tiene servicios `EXPRESO` y `FLEX`;
-   el SQL nuevo tiene `acuerdos_comerciales` y `acuerdo_condiciones`;
-   el SQL nuevo incorpora `origenes_cotizacion`;
-   el SQL nuevo contempla `cliente_integraciones`;
-   el SQL nuevo contempla integración futura con pedidos y Transoft.

Estas capacidades **no deben activarse automáticamente** solo porque
existan en la base.

Primero debe existir una regla de negocio explícita.

------------------------------------------------------------------------

# 88. Datos iniciales relevantes del SQL entregado

## Roles

``` text
ADMIN
CLIENTE
```

## Tipos de cliente

``` text
PUBLICO
B2B
B2B_PREMIUM
```

## Servicios

``` text
TRONCAL
PRIMERA_MILLA
ULTIMA_MILLA
PUERTA_PUERTA
EXPRESO
FLEX
```

## Unidades

``` text
KG
M3
PALLET
BULTO
VIAJE
KM
```

## Tipos de bulto

``` text
CAJA
PALLET
BOLSA
TAMBOR
MUEBLE
GRANEL
```

## Orígenes de cotización

``` text
WEB_PUBLICA
PANEL_CLIENTE
BACKOFFICE
API
```

------------------------------------------------------------------------

# 89. Datos comerciales de ejemplo

El SQL contiene un acuerdo:

``` text
Código: B2B_MUEBLES_01
Nombre: Acuerdo muebles zona norte
Descuento: 15%
Recargo: 0%
Vigencia desde: 2026-01-01
Sin vencimiento configurado
```

También contiene condiciones de ejemplo:

``` text
MINIMO_ENVIOS = 5
ZONA_COBERTURA = zona 1
TIPO_CARGA = "Muebles grandes"
SERVICIO_INCLUIDO = servicio 6
RETIRO_DOMICILIO = true
```

Estos datos son **datos de ejemplo del dump**, no reglas universales.

------------------------------------------------------------------------

# 90. Datos de margen de ejemplo

El SQL contiene:

``` text
Público:
30%

B2B:
20% hasta 2026-06-30
22% desde 2026-07-01

B2B Premium:
18%
```

Pero, nuevamente:

> Estos registros no deben provocar automáticamente un recargo si la
> tarifa base ya representa el precio final que SET cobra al cliente.

Debe existir una política explícita que determine si `margenes_ganancia`
se usa como costo→precio o si queda reservado para otro escenario.

------------------------------------------------------------------------

# 91. Regla final de implementación

Si existe una duda entre:

``` text
lo que parece lógico programar
```

y:

``` text
lo que realmente dice la especificación de negocio
```

no improvisar.

Marcar la duda:

``` text
TODO_NEGOCIO
```

y aislarla en una política/configuración.

Ejemplos:

``` text
TODO_NEGOCIO:
¿El descuento se aplica antes o después del seguro?

TODO_NEGOCIO:
¿El recargo se aplica sobre flete o total?

TODO_NEGOCIO:
¿SEGURO_INCLUIDO elimina totalmente el seguro?

TODO_NEGOCIO:
¿El margen se utiliza cuando la tarifa es precio final?

TODO_NEGOCIO:
¿La tarifa provincial funciona como fallback para última milla?
```

El código no debe esconder estas decisiones.

------------------------------------------------------------------------

# 92. Arquitectura final resumida

``` text
                         ┌──────────────────────┐
                         │      FRONTEND        │
                         │ React / Inertia      │
                         └──────────┬───────────┘
                                    │
                                    ▼
                         ┌──────────────────────┐
                         │ CotizacionController │
                         └──────────┬───────────┘
                                    │
                                    ▼
                         ┌──────────────────────┐
                         │   CotizadorService   │
                         └──────────┬───────────┘
                                    │
             ┌──────────────────────┼──────────────────────┐
             │                      │                      │
             ▼                      ▼                      ▼
       RutaResolver          TarifaResolver        AforoService
             │                      │                      │
             └──────────────────────┼──────────────────────┘
                                    │
                                    ▼
                       AcuerdoComercialService
                                    │
                                    ▼
                          PricingPolicy
                                    │
                                    ▼
                          ResultadoCotizacion
                                    │
                  ┌─────────────────┼─────────────────┐
                  │                 │                 │
                  ▼                 ▼                 ▼
             cotizaciones     resultados        auditoria
                  │
                  ▼
               pedido
                  │
                  ▼
             TransoftClient
                  │
                  ▼
          Transoft / Transporte
                  │
                  ▼
            tracking / estados
```

------------------------------------------------------------------------

# 93. Orden recomendado de desarrollo

## Fase 1 --- Motor

Implementar primero:

``` text
DTOs
AforoService
TarifaResolver
EscalonResolver
RutaResolver
CotizadorService
ResultadoCotizacion
```

## Fase 2 --- Tests

Crear todos los casos de regresión.

## Fase 3 --- Persistencia

Implementar:

``` text
CotizacionRepository
snapshots
versionado
```

## Fase 4 --- Frontend

Construir wizard.

## Fase 5 --- Clientes

Agregar:

``` text
login
cliente
acuerdo
condiciones
```

## Fase 6 --- Backoffice

Tarifas, mínimos, acuerdos y cotizaciones.

## Fase 7 --- Pedidos

Convertir:

``` text
cotización aceptada → pedido
```

## Fase 8 --- Transoft

Implementar:

``` text
crear carga
obtener tracking
obtener estados
webhook
```

## Fase 9 --- Portal cliente

Agregar:

``` text
pedidos
documentos
seguimiento
```

------------------------------------------------------------------------

# 94. Instrucción final para Claude

Implementar el proyecto siguiendo esta especificación.

**No rehacer toda la base de datos si las tablas existentes cubren el
requisito.**

**No introducir lógica de precios en React/TypeScript como fuente de
verdad.**

**No llamar a Transoft durante el cálculo de una cotización.**

**No hardcodear tarifas, descuentos, seguro, carga, descarga, mínimos ni
porcentajes configurables.**

**No aplicar automáticamente los registros de `margenes_ganancia` si
contradicen la regla vigente de que las tarifas cargadas representan
precio final.**

**No exponer costos internos al cliente.**

**No permitir que el frontend determine el precio final.**

**No permitir que un usuario cliente seleccione arbitrariamente otro
cliente o acuerdo.**

**No duplicar el motor para público y cliente.**

**Usar el mismo motor con distinto contexto comercial.**

**Guardar snapshots suficientes para reconstruir una cotización
histórica.**

**Versionar el algoritmo.**

**Agregar tests antes de cambiar reglas críticas.**

**Aislar las decisiones de negocio ambiguas en políticas/configuración y
marcarlas como `TODO_NEGOCIO` en lugar de inventarlas.**

------------------------------------------------------------------------

# 95. Fuentes utilizadas

-   `GUIA-COTIZADOR-SET.md` --- estado y lógica funcional del cotizador
    SET documentado al 16/09/2026.
-   `Documentacion_Cotizador_SET_LOGISTICA.docx` --- documentación
    técnica/operativa del cotizador vigente.
-   `cotizador_set.sql` --- esquema y datos iniciales del nuevo
    proyecto.
-   Lista de endpoints Transoft/Transoftware proporcionada para
    producción y entorno de prueba.

**La lista de APIs Transoft incluida en este documento fue proporcionada
por el proyecto. No se agregan endpoints externos no documentados en
esas fuentes.**
