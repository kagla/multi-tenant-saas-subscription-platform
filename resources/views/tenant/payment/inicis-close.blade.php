<!DOCTYPE html>
<html>
<head><title>결제창 닫기</title></head>
<body>
<script>
    // 이니시스 결제창 팝업 닫기
    if (window.opener) {
        window.opener.focus();
    }
    window.close();
</script>
<p style="text-align:center; margin-top:50px; color:#666;">결제가 취소되었습니다. 이 창을 닫아주세요.</p>
</body>
</html>
