<?php

namespace App\OpenApi;

/**
 * Definición global de la especificación OpenAPI (info, servers, seguridad, tags)
 * y de todos los esquemas de datos base (modelos y respuestas).
 *
 * Los modelos viven en el paquete greenter/core (carpeta vendor), que no se
 * anota directamente. Por eso los @OA\Schema se declaran aquí, reflejando el
 * mapeo del serializer (config/serializer/*.yml).
 *
 * Este archivo NO contiene endpoints. Cada endpoint se anota en su controlador.
 *
 * @OA\OpenApi(
 *     @OA\Info(
 *         version="1.3",
 *         title="Lycet API",
 *         description="API REST basado en Greenter - Facturación Electrónica.",
 *         @OA\Contact(name="Giancarlos Salas", email="giansalex@gmail.com")
 *     ),
 *     @OA\Server(url="http://lycet.api/api/v1"),
 *     @OA\Server(url="https://lycet.api/api/v1"),
 *     security={{"ApiToken": {}}}
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="ApiToken",
 *     type="apiKey",
 *     name="token",
 *     in="query",
 *     description="Api Token"
 * )
 *
 * @OA\Tag(name="invoice", description="Facturas y Boletas")
 * @OA\Tag(name="note", description="Notas de Credito y Debito")
 * @OA\Tag(name="summary", description="Resumen Diario de Boletas")
 * @OA\Tag(name="voided", description="Comunicacion de Bajas")
 * @OA\Tag(name="despatch", description="Guía de Remisión")
 * @OA\Tag(name="retention", description="Comprobante de Retenciones")
 * @OA\Tag(name="perception", description="Comprobante de Percepciones")
 * @OA\Tag(name="reversion", description="Comunicacion de Reversiones")
 * @OA\Tag(name="configuration", description="Configuraciones")
 * @OA\Tag(name="sale", description="Ventas en General")
 *
 * @OA\Schema(
 *     schema="Address",
 *     @OA\Property(property="ubigueo", type="string"),
 *     @OA\Property(property="codigoPais", type="string"),
 *     @OA\Property(property="departamento", type="string"),
 *     @OA\Property(property="provincia", type="string"),
 *     @OA\Property(property="distrito", type="string"),
 *     @OA\Property(property="urbanizacion", type="string"),
 *     @OA\Property(property="direccion", type="string"),
 *     @OA\Property(property="codLocal", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="Client",
 *     @OA\Property(property="tipoDoc", type="string"),
 *     @OA\Property(property="numDoc", type="string"),
 *     @OA\Property(property="rznSocial", type="string"),
 *     @OA\Property(property="address", ref="#/components/schemas/Address"),
 *     @OA\Property(property="email", type="string"),
 *     @OA\Property(property="telephone", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="Company",
 *     @OA\Property(property="ruc", type="string"),
 *     @OA\Property(property="razonSocial", type="string"),
 *     @OA\Property(property="nombreComercial", type="string"),
 *     @OA\Property(property="address", ref="#/components/schemas/Address"),
 *     @OA\Property(property="email", type="string"),
 *     @OA\Property(property="telephone", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="Charge",
 *     @OA\Property(property="codTipo", type="string"),
 *     @OA\Property(property="factor", type="number", format="float"),
 *     @OA\Property(property="monto", type="number", format="float"),
 *     @OA\Property(property="montoBase", type="number", format="float")
 * )
 *
 * @OA\Schema(
 *     schema="DetailAttribute",
 *     @OA\Property(property="code", type="string"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="value", type="string"),
 *     @OA\Property(property="fecInicio", type="string", format="date-time"),
 *     @OA\Property(property="fecFin", type="string", format="date-time"),
 *     @OA\Property(property="duracion", type="integer")
 * )
 *
 * @OA\Schema(
 *     schema="SaleDetail",
 *     @OA\Property(property="unidad", type="string"),
 *     @OA\Property(property="cantidad", type="number", format="float"),
 *     @OA\Property(property="codProducto", type="string"),
 *     @OA\Property(property="codProdSunat", type="string"),
 *     @OA\Property(property="codProdGS1", type="string"),
 *     @OA\Property(property="descripcion", type="string"),
 *     @OA\Property(property="mtoValorUnitario", type="number", format="float"),
 *     @OA\Property(property="descuento", type="number", format="float"),
 *     @OA\Property(property="igv", type="number", format="float"),
 *     @OA\Property(property="tipAfeIgv", type="string"),
 *     @OA\Property(property="isc", type="number", format="float"),
 *     @OA\Property(property="tipSisIsc", type="string"),
 *     @OA\Property(property="totalImpuestos", type="number", format="float"),
 *     @OA\Property(property="mtoPrecioUnitario", type="number", format="float"),
 *     @OA\Property(property="mtoValorVenta", type="number", format="float"),
 *     @OA\Property(property="mtoValorGratuito", type="number", format="float"),
 *     @OA\Property(property="mtoBaseIgv", type="number", format="float"),
 *     @OA\Property(property="porcentajeIgv", type="number", format="float"),
 *     @OA\Property(property="mtoBaseIsc", type="number", format="float"),
 *     @OA\Property(property="porcentajeIsc", type="number", format="float"),
 *     @OA\Property(property="mtoBaseOth", type="number", format="float"),
 *     @OA\Property(property="porcentajeOth", type="number", format="float"),
 *     @OA\Property(property="otroTributo", type="number", format="float"),
 *     @OA\Property(property="icbper", type="number", format="float"),
 *     @OA\Property(property="factorIcbper", type="number", format="float"),
 *     @OA\Property(property="cargos", type="array", @OA\Items(ref="#/components/schemas/Charge")),
 *     @OA\Property(property="descuentos", type="array", @OA\Items(ref="#/components/schemas/Charge")),
 *     @OA\Property(property="atributos", type="array", @OA\Items(ref="#/components/schemas/DetailAttribute"))
 * )
 *
 * @OA\Schema(
 *     schema="Legend",
 *     @OA\Property(property="code", type="string"),
 *     @OA\Property(property="value", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="Document",
 *     @OA\Property(property="tipoDoc", type="string"),
 *     @OA\Property(property="nroDoc", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="SalePerception",
 *     @OA\Property(property="codReg", type="string"),
 *     @OA\Property(property="porcentaje", type="number", format="float"),
 *     @OA\Property(property="mtoBase", type="number", format="float"),
 *     @OA\Property(property="mto", type="number", format="float"),
 *     @OA\Property(property="mtoTotal", type="number", format="float")
 * )
 *
 * @OA\Schema(
 *     schema="Direction",
 *     @OA\Property(property="ubigueo", type="string"),
 *     @OA\Property(property="direccion", type="string"),
 *     @OA\Property(property="codLocal", type="string"),
 *     @OA\Property(property="ruc", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="EmbededDespatch",
 *     @OA\Property(property="llegada", ref="#/components/schemas/Direction"),
 *     @OA\Property(property="partida", ref="#/components/schemas/Direction"),
 *     @OA\Property(property="transportista", ref="#/components/schemas/Client"),
 *     @OA\Property(property="nroLicencia", type="string"),
 *     @OA\Property(property="transpPlaca", type="string"),
 *     @OA\Property(property="transpCodeAuth", type="string"),
 *     @OA\Property(property="transpMarca", type="string"),
 *     @OA\Property(property="modTraslado", type="string"),
 *     @OA\Property(property="pesoBruto", type="number", format="float"),
 *     @OA\Property(property="undPesoBruto", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="Prepayment",
 *     @OA\Property(property="tipoDocRel", type="string"),
 *     @OA\Property(property="nroDocRel", type="string"),
 *     @OA\Property(property="total", type="number", format="float")
 * )
 *
 * @OA\Schema(
 *     schema="Detraction",
 *     @OA\Property(property="percent", type="number", format="float"),
 *     @OA\Property(property="mount", type="number", format="float"),
 *     @OA\Property(property="ctaBanco", type="string"),
 *     @OA\Property(property="codMedioPago", type="string"),
 *     @OA\Property(property="codBienDetraccion", type="string"),
 *     @OA\Property(property="valueRef", type="number", format="float")
 * )
 *
 * @OA\Schema(
 *     schema="PaymentTerms",
 *     @OA\Property(property="moneda", type="string"),
 *     @OA\Property(property="tipo", type="string"),
 *     @OA\Property(property="monto", type="number", format="float")
 * )
 *
 * @OA\Schema(
 *     schema="Cuota",
 *     @OA\Property(property="moneda", type="string"),
 *     @OA\Property(property="monto", type="number", format="float"),
 *     @OA\Property(property="fechaPago", type="string", format="date-time")
 * )
 */
final class OpenApiDefinition
{
}
