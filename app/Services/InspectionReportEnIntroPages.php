<?php

namespace App\Services;

/** Fixed English introduction HTML blocks for EN inspection reports. */
final class InspectionReportEnIntroPages
{
    public static function htmlBlocks(): array
    {
        return [
            <<<'HTML'
<div style="text-align:left; font-family: arialbd;">
    <h1 style="font-family:arialbd; color:#c00000; font-size:15.5pt; text-align:center; margin-bottom:4px;">GIS Property Inspection &amp; Valuation</h1>
    <h2 style="font-family:arialbd; color:#c00000; font-size:13pt; text-decoration:underline; text-align:center; margin-bottom:10px;">About GIS — Company Overview &amp; Services</h2>

    <p style="font-size:12.5pt; font-family:arialbd; font-weight:bold; color:#000; margin-bottom:4px;">Who We Are</p>
    <p style="font-size:10.5pt; color:#222; margin-top:0px; margin-bottom:7px; line-height:1.55;">At <strong>GIS</strong>, we believe property safety starts with the right decision. We provide a comprehensive inspection of ready-built properties before purchase or occupancy, to record visible observations and defects at the time of the visit, using inspection equipment and preliminary tests within the agreed scope of service.</p>
    <p style="font-size:10.5pt; color:#222; margin-top:0px; margin-bottom:7px; line-height:1.55;">We work through a specialized technical team and use appropriate inspection equipment to document findings and test results, and to prepare a report that describes the condition of inspected elements, observations, and recommendations — helping the client make a clearer purchase or occupancy decision.</p>
    <p style="font-size:10.5pt; color:#222; margin-top:0px; margin-bottom:10px; line-height:1.55;">We are among the leading property inspection firms in Bahrain, with a strong track record serving individuals, companies, and developers.</p>

    <h2 style="font-family:arialbd; color:#c00000; font-size:13pt; text-decoration:underline; margin-bottom:4px; margin-top:6px;">Main Services</h2>
    <p style="font-size:11pt; font-family:arialbd; font-weight:bold; color:#c00000; margin-top:0px; margin-bottom:6px;">GIS inspects ready-built properties across the following systems:</p>
    <table cellpadding="3" cellspacing="0">
        <tr><td style="font-size:10pt; line-height:14px"><strong>1. Electrical:</strong> Inspect accessible outlets, switches, panels, and visible connections; carry out electrical tests and measurements within the service scope; document results, observations, and inspection limits in the attached electrical report.</td></tr>
        <tr><td style="font-size:10pt; line-height:14px"><strong>2. Plumbing:</strong> Inspect accessible piping, fittings, and sanitary fixtures; check drainage and operation within the service scope; document water pressure and salinity readings when taken; record any leaks or performance shortfalls observed during the inspection.</td></tr>
        <tr><td style="font-size:10pt; line-height:14px"><strong>3. Moisture &amp; Leakage:</strong> Record visible moisture and leak traces; use appropriate meters and tests within the inspection scope; note cases that need further investigation to identify the source.</td></tr>
        <tr><td style="font-size:10pt; line-height:14px"><strong>4. Tanks:</strong> Inspect accessible tank parts, covers, and connections; record leak signs and visible cleanliness notes; water quality analysis is not performed.</td></tr>
        <tr><td style="font-size:10pt; line-height:14px"><strong>5. Doors &amp; Windows:</strong> Check opening/closing, handles, locks, and hinges; inspect frames, glass, and visible seals; record notes on fixing and finish.</td></tr>
        <tr><td style="font-size:10pt; line-height:14px"><strong>6. Ceilings &amp; Walls:</strong> Inspect cracks, moisture traces, and finish quality on accessible surfaces; document observations and measurements taken; note cases that require specialist assessment.</td></tr>
        <tr><td style="font-size:10pt; line-height:14px"><strong>7. Tiles &amp; Flooring:</strong> Grouting, levelness, stability, and installation quality.</td></tr>
        <tr><td style="font-size:10pt; line-height:14px"><strong>8. Safety Systems:</strong> Inspect available devices such as CCTV, smoke detectors, and alarm sensors; document operational tests performed; state devices that could not be tested.</td></tr>
        <tr><td style="font-size:10pt; line-height:14px"><strong>9. HVAC:</strong> Inspect AC units, air outlets, exhaust fans, and accessible ducting; document operation and measurements taken; state parts that could not be inspected or tested.</td></tr>
        <tr><td style="font-size:10pt; line-height:14px"><strong>10. Parking &amp; Facades:</strong> Surfaces, shading, garage doors, cracks, and expansion joints.</td></tr>
    </table>
</div>
HTML,
            <<<'HTML'
<div style="text-align:left; font-family: arialbd;">
    <h1 style="font-family:arialbd; color:#c00000; font-size:15pt; text-decoration:underline; margin-bottom:8px; margin-top:0px;">What Sets Us Apart</h1>

    <h2 style="font-family:arialbd; color:#c00000; font-size:13pt; text-decoration:underline; margin-top:6px; margin-bottom:4px;">GIS Inspection &amp; Documentation Methodology</h2>
    <p style="font-size:11.5pt; margin-top:0px; margin-bottom:2px;">• <strong>Documenting inspection results:</strong></p>
    <p style="font-size:11pt; color:#222; margin-top:0px; margin-bottom:8px; line-height:1.55;">We prepare a report documenting observations on elements that were accessible and inspected at the time of the visit, supported by photos and appropriate recommendations, and clarifying inspection limits and elements that could not be inspected. The report describes the visible condition within the agreed scope of service and does not guarantee detection of all defects or the future condition of the property.</p>

    <p style="font-size:11.5pt; margin-top:0px; margin-bottom:2px;">• <strong>Recording safety observations:</strong></p>
    <p style="font-size:11pt; color:#222; margin-top:0px; margin-bottom:8px; line-height:1.55;">We record visible safety observations on elements that could be inspected at the time of the visit, according to the service scope and tests performed, and we note what requires remediation or specialist inspection. Absence of a recorded observation does not mean the element is free of hidden or later defects.</p>

    <h2 style="font-family:arialbd; color:#c00000; font-size:13pt; text-decoration:underline; margin-top:8px; margin-bottom:4px;">Our Advantages</h2>
    <table cellpadding="3.5">
        <tr><td style="font-size:11.5pt; line-height:17px">• Fast response and inspection completion.</td></tr>
        <tr><td style="font-size:11.5pt; line-height:17px">• Simple request and report delivery process.</td></tr>
        <tr><td style="font-size:11.5pt; line-height:17px">• Photo and technical documentation of observations during the inspection, including tests and measurements performed.</td></tr>
        <tr><td style="font-size:11.5pt; line-height:17px">• Professional, readable, detailed reports.</td></tr>
        <tr><td style="font-size:11.5pt; line-height:17px">• Organized archive for each property unit.</td></tr>
    </table>

    <h2 style="font-family:arialbd; color:#c00000; font-size:13pt; text-decoration:underline; margin-top:8px; margin-bottom:4px;">Inspection &amp; Delivery Process</h2>
    <table cellpadding="3.5">
        <tr><td style="font-size:11.5pt; line-height:17px">• Inspection typically takes 2 hours to 2 days depending on size and system complexity.</td></tr>
        <tr><td style="font-size:11.5pt; line-height:17px">• Final report is delivered within five business days from completion of the site inspection, after technical review.</td></tr>
        <tr><td style="font-size:11.5pt; line-height:17px">• Please send any questions or comments on the report within three business days of receipt for review, clarification, and correction of verified errors. Any technical revisions are based on inspection findings and available evidence.</td></tr>
    </table>
</div>
HTML,
            <<<'HTML'
<div style="text-align:left; font-family: arialbd;">
    <h1 style="font-family:arialbd; color:#c00000; font-size:16pt; text-decoration:underline; margin-bottom:10px;">Optional Documents That Improve Report Quality</h1>
    <p style="font-size:12.5pt; color:#222; margin-top:0px; margin-bottom:10px; line-height:1.6;">The following documents improve technical accuracy. Please attach whatever is available with your inspection request:</p>

    <p style="font-size:13.5pt; font-weight:bold; margin-top:10px; margin-bottom:6px; color:#c00000;">1. Engineering &amp; Technical Documents</p>
    <table cellpadding="5">
        <tr><td style="font-size:12.5pt; line-height:19px">• Building permit</td></tr>
        <tr><td style="font-size:12.5pt; line-height:19px">• Approved drawings (architectural, structural, mechanical, electrical)</td></tr>
        <tr><td style="font-size:12.5pt; line-height:19px">• Structural warranty certificate, if available, stating issuer, duration, and scope</td></tr>
        <tr><td style="font-size:12.5pt; line-height:19px">• Soil test reports</td></tr>
        <tr><td style="font-size:12.5pt; line-height:19px">• Material/system warranties such as: plumbing, electrical, HVAC, waterproofing, aluminum &amp; glass</td></tr>
    </table>

    <p style="font-size:13.5pt; font-weight:bold; margin-top:10px; margin-bottom:6px; color:#c00000;">2. General Information &amp; Location</p>
    <table cellpadding="5">
        <tr><td style="font-size:12.5pt; line-height:19px">• Approved plot maps from the engineering office</td></tr>
        <tr><td style="font-size:12.5pt; line-height:19px">• Property location (direct link or map screenshot)</td></tr>
        <tr><td style="font-size:12.5pt; line-height:19px">• Address certificate (house no., street, road, compound)</td></tr>
    </table>

    <p style="font-size:13.5pt; font-weight:bold; margin-top:10px; margin-bottom:6px; color:#c00000;">3. Additional Supporting Documents</p>
    <table cellpadding="5">
        <tr><td style="font-size:12.5pt; line-height:19px">• Ground bearing certificate (if available)</td></tr>
        <tr><td style="font-size:12.5pt; line-height:19px">• Approximate property age (year built or handed over)</td></tr>
        <tr><td style="font-size:12.5pt; line-height:19px">• Copy of buyer / client ID card</td></tr>
        <tr><td style="font-size:12.5pt; line-height:19px">• Main contractor agreement (optional)</td></tr>
        <tr><td style="font-size:12.5pt; line-height:19px">• Building material warranties (optional)</td></tr>
    </table>
</div>
HTML,
            <<<'HTML'
<div style="text-align:left; font-family: arialbd;">
    <h1 style="font-family:arialbd; color:#c00000; font-size:16pt; text-decoration:underline; margin-bottom:10px;">Important Disclaimer — Limits of Liability</h1>
    <p style="font-size:12.5pt; color:#222; margin-top:0px; margin-bottom:8px; line-height:1.65;"><strong>GIS</strong> performs its services with the highest accuracy and impartiality, using technical tools and qualified experts.</p>
    <p style="font-size:12.5pt; color:#222; margin-top:0px; margin-bottom:8px; line-height:1.65;">Inspection work is carried out with professional care within the agreed scope of service. Company responsibilities are defined by the contract and applicable laws; stating inspection limits is not an absolute waiver of liability.</p>
    <p style="font-size:12.5pt; color:#222; margin-top:0px; margin-bottom:10px; line-height:1.65;">The service is limited to inspection, documentation, and report preparation within the agreed scope. It does not include managing disputes between the client and contractor or developer, representing any party, or supervising repairs, except under a separate agreement and within the required competence and licenses.</p>

    <h2 style="font-family:arialbd; color:#c00000; font-size:13.5pt; margin-top:8px; margin-bottom:4px;">* Important notice:</h2>
    <p style="font-size:12pt; color:#222; margin-top:0px; margin-bottom:10px; line-height:1.6;">GIS inspects and documents the visible condition of the property at the time of the visit only. The report is not a future warranty of property condition or later developments. The report reflects the condition of inspected elements at the time of the visit; the service does not include follow-up of later changes or verification of repairs, except under a separate agreement for follow-up or re-inspection.</p>

    <h2 style="font-family:arialbd; color:#c00000; font-size:14pt; text-decoration:underline; margin-top:10px; margin-bottom:6px;">Frequently Asked Questions</h2>
    <table cellpadding="4.5">
        <tr><td style="font-size:12pt; line-height:18px;"><strong style="font-family:arialbd; color:red; font-size:13pt;">Can I get a refund?</strong><br/> Cancellation and refund requests are reviewed under the signed contract terms, with cancellation fees and any refundable amount clarified where applicable.</td></tr>
        <tr><td style="font-size:12pt; line-height:18px;"><strong style="font-family:arialbd; color:red; font-size:13pt;">Can the report be revised?</strong><br/> Yes — comments are reviewed and verified errors are corrected, provided technical revisions are based on inspection findings and available evidence. Please send comments within three business days of receiving the report.</td></tr>
        <tr><td style="font-size:12pt; line-height:18px;"><strong style="font-family:arialbd; color:red; font-size:13pt;">Do you provide maintenance?</strong><br/> No — we are an independent inspection body only.</td></tr>
        <tr><td style="font-size:12pt; line-height:18px;"><strong style="font-family:arialbd; color:red; font-size:13pt;">How is the inspector assigned?</strong><br/> The inspection team is assigned based on property type, service scope, and required specialty. Observations, photos, and test results are collected, then reviewed and organized into the GIS report before delivery to the client.</td></tr>
        <tr><td style="font-size:12pt; line-height:18px;"><strong style="font-family:arialbd; color:red; font-size:13pt;">Why is inspection important?</strong><br/> It helps you understand the visible condition of the property before purchase or handover by documenting observations and test results and prioritizing remediation — supporting a clearer decision.</td></tr>
    </table>
</div>
HTML,
        ];
    }
}
