

<footer id="footer" class="hidden-print secondaryContent container-fluid">
	<section class="container-fluid footerText">
		Copyright &copy; {{YEAR}}, <a href='{{COPYRIGHT_URL}}' target='_BLANK' title='{{COPYRIGHT_OWNER}}'>{{COPYRIGHT_OWNER}}</a>, All Rights Reserved.
	</section>
</footer>

</div>

<!-- IMS Scripts -->
<script type="text/javascript" src="{{JS_URL}}/jquery.min.js"></script>
<script type="text/javascript" src="{{JS_URL}}/bootstrap.min.js"></script>
<script type="text/javascript" src="{{JS_URL}}/ims-core.js"></script>
{% for JS in ADDON_JS %}
	<script type="text/javascript" src="{{JS_URL}}/{{JS}}"></script>
{% endfor %}
<!-- /IMS Scripts -->

</body>
</html>