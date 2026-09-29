package com.itnoted.mobile;

import android.app.Activity;
import android.content.ActivityNotFoundException;
import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.webkit.CookieManager;
import android.webkit.DownloadListener;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Toast;

public final class MainActivity extends Activity {
    private static final int FILE_CHOOSER_REQUEST = 72;
    private static final String APP_URL = "https://itnoted.infy.click/";
    private static final String APP_HOST = "itnoted.infy.click";

    private WebView webView;
    private ValueCallback<Uri[]> fileChooserCallback;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        getWindow().setStatusBarColor(0xFF1F6F63);
        getWindow().setNavigationBarColor(0xFF14191C);

        webView = new WebView(this);
        setContentView(webView);

        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setDatabaseEnabled(true);
        settings.setAllowFileAccess(true);
        settings.setAllowContentAccess(true);
        settings.setMixedContentMode(WebSettings.MIXED_CONTENT_NEVER_ALLOW);
        CookieManager.getInstance().setAcceptCookie(true);

        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                return openExternalIfNeeded(request.getUrl());
            }

            @Override
            public void onPageFinished(WebView view, String url) {
                super.onPageFinished(view, url);
                view.evaluateJavascript(
                    "document.addEventListener('click',function(e){var a=e.target.closest('a[target=_blank]');" +
                    "if(a){e.preventDefault();window.ITNotedBridge.open(a.href);}},true);", null);
            }
        });

        webView.addJavascriptInterface(new Object() {
            @android.webkit.JavascriptInterface
            public void open(String url) {
                runOnUiThread(() -> {
                    Uri uri = Uri.parse(url);
                    if (isAppOrigin(uri)) webView.loadUrl(uri.toString());
                    else openExternalIfNeeded(uri);
                });
            }
        }, "ITNotedBridge");

        webView.setWebChromeClient(new WebChromeClient() {
            @Override
            public boolean onShowFileChooser(WebView view, ValueCallback<Uri[]> callback, FileChooserParams params) {
                if (fileChooserCallback != null) fileChooserCallback.onReceiveValue(null);
                fileChooserCallback = callback;
                try {
                    startActivityForResult(params.createIntent(), FILE_CHOOSER_REQUEST);
                    return true;
                } catch (ActivityNotFoundException exception) {
                    fileChooserCallback = null;
                    Toast.makeText(MainActivity.this, "Tidak ada aplikasi untuk memilih file.", Toast.LENGTH_SHORT).show();
                    return false;
                }
            }
        });

        webView.setDownloadListener((url, userAgent, contentDisposition, mimeType, contentLength) -> {
            try {
                startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse(url)));
            } catch (ActivityNotFoundException exception) {
                Toast.makeText(this, "Tidak ada aplikasi untuk membuka unduhan ini.", Toast.LENGTH_SHORT).show();
            }
        });

        if (savedInstanceState == null) webView.loadUrl(APP_URL);
        else webView.restoreState(savedInstanceState);
    }

    private boolean openExternalIfNeeded(Uri uri) {
        if (isAppOrigin(uri)) return false;
        if (!"http".equalsIgnoreCase(uri.getScheme()) && !"https".equalsIgnoreCase(uri.getScheme())) return true;
        try {
            startActivity(new Intent(Intent.ACTION_VIEW, uri));
        } catch (ActivityNotFoundException exception) {
            Toast.makeText(this, "Tidak ada browser untuk membuka tautan.", Toast.LENGTH_SHORT).show();
        }
        return true;
    }

    private boolean isAppOrigin(Uri uri) {
        return "https".equalsIgnoreCase(uri.getScheme()) && APP_HOST.equalsIgnoreCase(uri.getHost());
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        if (requestCode != FILE_CHOOSER_REQUEST || fileChooserCallback == null) return;
        Uri[] result = null;
        if (resultCode == RESULT_OK && data != null) {
            if (data.getClipData() != null) {
                int count = data.getClipData().getItemCount();
                result = new Uri[count];
                for (int i = 0; i < count; i++) result[i] = data.getClipData().getItemAt(i).getUri();
            } else if (data.getData() != null) result = new Uri[] { data.getData() };
        }
        fileChooserCallback.onReceiveValue(result);
        fileChooserCallback = null;
    }

    @Override
    protected void onSaveInstanceState(Bundle outState) {
        super.onSaveInstanceState(outState);
        if (webView != null) webView.saveState(outState);
    }

    @Override
    public void onBackPressed() {
        if (webView != null && webView.canGoBack()) webView.goBack();
        else super.onBackPressed();
    }

    @Override
    protected void onDestroy() {
        if (fileChooserCallback != null) fileChooserCallback.onReceiveValue(null);
        if (webView != null) webView.destroy();
        super.onDestroy();
    }
}
