<footer class="site-footer">
    <div class="site-footer__inner">
        <div class="site-footer__cols">
            <div class="footer-col">
                <h3>Shop</h3>
                <a href="/new">New &amp; Featured</a>
                <a href="/men">Men</a>
                <a href="/women">Women</a>
                <a href="/kids">Kids</a>
                <a href="/sale">Sale</a>
            </div>
            <div class="footer-col">
                <h3>Help</h3>
                <a href="/help">Help Center</a>
                <a href="/shipping">Shipping &amp; Delivery</a>
                <a href="/returns">Returns &amp; Exchanges</a>
                <a href="/size-guides">Size Guides</a>
                <a href="/contact">Contact Us</a>
            </div>
            <div class="footer-col">
                <h3>Company</h3>
                <a href="/about">About BuyFirst</a>
                <a href="/careers">Careers</a>
                <a href="/sustainability">Sustainability</a>
                <a href="/journal">Journal</a>
                <a href="/membership">BuyFirst Club</a>
                <a href="/stores">Store Locator</a>
            </div>
            <div class="footer-col">
                <h3>Legal</h3>
                <a href="/privacy">Privacy Policy</a>
                <a href="/terms">Terms &amp; Conditions</a>
                <a href="/cookies">Cookie Policy</a>
                <form class="footer-consent" method="post" action="/consent">
                    <?= csrf_field() ?>
                    <input type="hidden" name="return" value="/cookies">
                    <button type="submit" name="choice" value="reset">Cookie settings</button>
                </form>
                <a href="/accessibility">Accessibility</a>
                <a href="/imprint">Company Information</a>
            </div>
        </div>
        <div class="site-footer__bottom">
            <p class="site-footer__brand">BUYFIRST<span>.</span> <em>Gear up. Move first.</em></p>
            <p>&copy; <?= date('Y') ?> BuyFirst Ltd. All rights reserved.</p>
        </div>
    </div>
</footer>
