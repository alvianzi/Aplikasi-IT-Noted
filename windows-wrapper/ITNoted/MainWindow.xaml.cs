using System.Diagnostics;
using System.Windows;
using Microsoft.Web.WebView2.Core;

namespace ITNoted;

public partial class MainWindow : Window
{
    private const string AppUrl = "https://itnoted.infy.click/";
    private const string AppHost = "itnoted.infy.click";

    public MainWindow()
    {
        InitializeComponent();
        Loaded += OnLoaded;
    }

    private async void OnLoaded(object sender, RoutedEventArgs e)
    {
        try
        {
            await Browser.EnsureCoreWebView2Async();
            Browser.CoreWebView2.NewWindowRequested += OnNewWindowRequested;
            Browser.CoreWebView2.NavigationStarting += OnNavigationStarting;
            Browser.Source = new Uri(AppUrl);
        }
        catch (Exception)
        {
            MessageBox.Show(
                "IT Noted memerlukan Microsoft Edge WebView2 Runtime. Jalankan ulang installer saat terhubung ke internet, atau pasang WebView2 Runtime dari Microsoft.",
                "Komponen browser belum tersedia", MessageBoxButton.OK, MessageBoxImage.Warning);
            Close();
        }
    }

    private void OnNavigationStarting(object? sender, CoreWebView2NavigationStartingEventArgs e)
    {
        if (IsAppUrl(e.Uri)) return;
        e.Cancel = true;
        OpenExternal(e.Uri);
    }

    private void OnNewWindowRequested(object? sender, CoreWebView2NewWindowRequestedEventArgs e)
    {
        e.Handled = true;
        OpenExternal(e.Uri);
    }

    private static bool IsAppUrl(string value)
    {
        return Uri.TryCreate(value, UriKind.Absolute, out var uri)
            && uri.Scheme == Uri.UriSchemeHttps
            && string.Equals(uri.Host, AppHost, StringComparison.OrdinalIgnoreCase);
    }

    private static void OpenExternal(string value)
    {
        if (!Uri.TryCreate(value, UriKind.Absolute, out var uri)
            || (uri.Scheme != Uri.UriSchemeHttp && uri.Scheme != Uri.UriSchemeHttps)) return;

        Process.Start(new ProcessStartInfo(uri.AbsoluteUri) { UseShellExecute = true });
    }
}
