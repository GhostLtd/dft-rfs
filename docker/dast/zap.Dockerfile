# The DAST stack's ZAP (compose.dast.yaml): the pinned ZAP image plus the Authentication Statistics add-on, which
# records logged-in/out counts per component and site, so a scan can prove its active scanner's requests were logged
# in (see docs/Dast.md). Not bundled with ZAP, and the scan network has no route out, so it's added here, at build
# time, pinned by checksum (from ZAP's add-on index, ZapVersions-2.17.xml). Bump both by hand.
FROM ghcr.io/zaproxy/zaproxy:2.17.0

ADD --chmod=644 \
    --checksum=sha256:cfb604c27f3a7a58e7b5aa55fe9f19a9ce5561fab3ef7d3f6c72845671fb5dcf \
    https://github.com/zaproxy/zap-extensions/releases/download/authstats-v2/authstats-alpha-2.zap \
    /zap/plugin/authstats-alpha-2.zap
