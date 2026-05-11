window.DIAWidget = {
	endPoint: "https://www.whip2go.com/",
	shadow: null,
	miles: null,
	fee: null,
	milesVal: null,
	feeVal: null,
	costOutput: null,
	matrix: {},
	vehicle: null,
	buttonCss: "",
    buttonText: "",

	init: function (buttonText='Build Your Own Program',buttonCss={'background-color':'#000','color':'#fff','text-align':'center','width':'100%'}) {
		console.log("DIAWidget constructor called vinod", buttonCss);
		// Initialize properties
		this.buttonCss = buttonCss;
        this.buttonText = buttonText;

		setTimeout(() => {
			this._buttonInt();
		},2000);
		setTimeout(() => {
			this._domInt();
		},5000);
	},

	_buttonInt: function () {
		var self = this; // Capture `this` to use inside callback
		document.querySelectorAll('#auto-open-dia-widget[data-vin]').forEach(function (elm) {
            if (elm.dataset.vin && elm.dataset.vin.length >= 17) {
				const containr = document.createElement("div");
				containr.id = "diawidget-vin-modal";
				containr.style = `position:relative;width:100%;height:100%;background:#fff;z-index:9999;display:block;margin:0 auto;`;
				var btnCss = self.cssObjectToString(self.buttonCss);

				var html =`<p><button onclick="#" style="{buttonCss}">{buttonText}</button></p>`;

				let content = html.replace("{buttonCss}", btnCss);
				content = content.replace("{buttonText}", self.buttonText);
				containr.innerHTML = `${content}`;
				elm.appendChild(containr);
			}
		});
	},
	_domInt: function () {
		const self = this; // Capture `this` to use inside callback
		document.querySelectorAll('#auto-open-dia-widget[data-vin]').forEach(function (elm) {
            if (elm.dataset.vin) {
                self.display(elm);
            }
		});
	},
    
    cssObjectToString:function(cssObject) {
        return Object.entries(cssObject)
          .map(([key, value]) => {
            // Convert camelCase to kebab-case
            const kebabKey = key.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
            return `${kebabKey}:${value}`;
          })
          .join('; ');
    },

	initilize: function () {
		const s = this.shadow;
		this.miles = s.getElementById("miles");
		this.fee = s.getElementById("fee");
		this.milesVal = s.getElementById("miles-val");
		this.feeVal = s.getElementById("fee-val");
		this.costOutput = s.getElementById("cost");

		this.miles.addEventListener("input", () => this.updateWidget());
		this.fee.addEventListener("input", () => this.updateWidget());
		s.querySelectorAll('input[name="freq"]').forEach((radio) => {
			radio.addEventListener("change", () => this.updateWidget());
		});

		this.updateWidget();
	},

	open: function (vin) {
		const existingModal = document.getElementById("diawidget-modal");
		if (existingModal) existingModal.remove();

		const modal = document.createElement("div");
		modal.id = "diawidget-modal";
		modal.style = `
            position:fixed;top:0;left:0;width:100%;height:100%;
            background:#00000088;z-index:9999;display:flex;
            align-items:center;justify-content:center;
        `;

		const content = document.createElement("div");
		content.style = `
            background:white;padding:20px;border-radius:12px;
            max-width:700px;width:90%;position:relative;
            box-shadow:0 5px 20px rgba(0,0,0,0.3);
        `;
		modal.appendChild(content);
		document.body.appendChild(modal);
		// Shadow root created in this content div
		this.shadow = content.attachShadow({ mode: "open" });

		fetch(this.endPoint + `widgets/widgets/program/${vin}/${window?.location?.hostname || "localhost"}`)
			.then((res) => res.json())
			.then((html) => {
				if (html.error) return;
				this.matrix = html.calculations;
				this.vehicle = html.vehicle;
				this.program(vin);
			})
			.catch((e) => {
				console.log(e,"Unable to fetch finance info. Please try again later.");
			});
	},

	display: function (ele) {
        var self = this;
		let vin = ele.dataset.vin;
		if(vin.length < 17){
			return;
		}
		const container = document.createElement("div");
		container.id = "diawidget-vin-modal";
		container.style = `
            position:relative;width:100%;height:100%;
            background:#fff;z-index:9999;display:block;margin:0 auto;
        `;
        var btnCss = self.cssObjectToString(self.buttonCss);
		fetch(this.endPoint + `widgets/widgets/index/${vin}`)
			.then((res) => res.json())
			.then((html) => {
                ele.innerHTML='';
				if (html.error) {
					return;
				}
                let content = (html.html).replace('{buttonCss}',btnCss);
                content=content.replace('{buttonText}',self.buttonText);
				container.innerHTML = `${content}`;
				ele.appendChild(container);
			})
			.catch((e) => {
				console.log("Unable to fetch finance info. Please try again later.",e);
			});
	},

	program: function (vin) {
		console.log(this.matrix);
		const initial_fee_options = Object.keys(
			this.matrix.initial_fee_options ?? {}
		).map(Number);
		const minInitialFee = Math.min(...initial_fee_options);
		const maxInitialFee = Math.max(...initial_fee_options);
		const initialfeestep = initial_fee_options[1]
			? initial_fee_options[1] - initial_fee_options[0]
			: 100;
		const miles_options = Object.keys(this.matrix.miles_options ?? {}).map(
			Number
		);
		const minMiles = Math.min(...miles_options);
		const maxMiles = Math.max(...miles_options);
		const widgetHTML =
			`
            <style>
                * { box-sizing: border-box; }
                .widget { border: 1px solid #ccc; padding: 20px; max-width: 650px; font-family: sans-serif; background:#fff; }
                label { display: block; margin-top: 10px; font-weight: bold; }
                input[type="range"] { width: 100%; }
                .frequency label { margin-right: 10px; font-weight: normal; }
                .summary { margin-top: 15px; font-size: 16px; font-weight: bold; }
                .widget p{margin: 0 0 10px; font-size: 14px; line-height: 1.5;}
                .book-block button{background-color: #0e175f;color: #fff;padding: 10px 20px;border-radius: 10px;border: 1px solid #0e175f;cursor: pointer;min-width: 200px; }
            </style>
            
            <div class="widget">
                <button onclick="document.getElementById('diawidget-modal').remove()" style="position: absolute;top: 2px;right: 15px; font-size: 30px;background: none;border: none;cursor: pointer;">✖</button>
               <div class="logo-block" style="text-align: center; margin-bottom: 20px;">
                    <img src="` +
			this.endPoint +
			`img/free2move.png" alt="DriveItAway Logo" style="width: 150px;height: auto;">
                    <img src="` +
			this.endPoint +
			`img/diawidget.png" alt="DriveItAway Logo" style="width: 150px;height: auto;">
                    
                </div>
                <div class="info-block">
                    <p>DriveItAway is a <strong>Vehicle Flexible Lease</strong> program where you <strong>Pay-As-You-Go</strong>. <strong>Any credit is welcome. Lease for 1 month at a time. At the end of each cycle, you can renew, purchase, or return. Your choice!</strong></p>
                    <p>You pay for use of the car. Each payment will <strong>reduce the purchase price of the car</strong> if you decide to buy the car.</p>
                </div>
                <div class="program-block">
                <h3>Build Your Program</h3>

                <label>Miles per Month</label>
                <input type="range" id="miles" min="` +
			minMiles +
			`" max="` +
			maxMiles +
			`" step="500" value="`+minMiles+`">
                <div><strong><span id="miles-val">1000</span> miles</strong></div>

                <label>Initial Fee</label>
                <input type="range" id="fee" min="` +
			minInitialFee +
			`" max="` +
			maxInitialFee +
			`" step="` +
			initialfeestep +
			`" value="`+minInitialFee+`">
                <div><strong>$<span id="fee-val">500</span></strong></div>

                <label>Prepay</label>
                <div class="frequency" style="display:inline-flex;">
                    <label><input type="radio" name="freq" value="weekly" checked> Weekly</label>
                    <label><input type="radio" name="freq" value="biweekly"> Biweekly</label>
                    <label><input type="radio" name="freq" value="monthly"> Monthly</label>
                </div>

                <div class="summary">Rate: <span id="cost">141.05</span></div>
                </div>
                <div class="book-block" style="text-align: center; margin: 10px;"><button onclick="window.DIAWidget.openDIABooking('${vin}')">Proceed to Order through DriveItAway</button></div>
            </div>
        `;
		this.shadow.innerHTML = widgetHTML;
		this.initilize();
	},

	updateWidget: function () {
		const milesValue = parseInt(this.miles.value, 10);
		const feeValue = parseInt(this.fee.value, 10);
		const frequency = this.shadow.querySelector(
			'input[name="freq"]:checked'
		).value;

		this.milesVal.textContent = milesValue;
		this.feeVal.textContent = feeValue;

		var tier_rental = this.matrix.tier_rental ?? {};
		// console.log(tier_rental);
		var comb = feeValue + "X" + milesValue;
		var rentalObj = {};
		if (typeof tier_rental[comb] !== "undefined") {
			rentalObj = tier_rental[comb];
			// console.log(comb,rentalObj,"Key exists");
		} else {
			return;
		}
		estimatedWeeklyCost = rentalObj.weekkEmfRent ?? 0;
		if (frequency === "biweekly") {
			estimatedWeeklyCost = rentalObj.biWeeklyEmfRent ?? 0;
		} else if (frequency === "monthly") {
			estimatedWeeklyCost = rentalObj.monthlyEmfRent ?? 0;
		}
		this.costOutput.textContent = estimatedWeeklyCost.replace(/[()]/g, "");
	},
	openDIABooking: function (vin) {
		fetch(
			this.endPoint +
				`widgets/widgets/save/${vin}/${window?.location?.hostname || "localhost"}`,
			{
				method: "POST",
				headers: {
					"Content-Type": "application/x-www-form-urlencoded",
				},
				body: {widget: 1 },
			}
		)
			.then((res) => res.json())
			.then((data) => {
				if (data.status) {
					const existingModal = document.getElementById("diawidget-modal");
					if (existingModal) existingModal.remove();
					// window.open(data.redirect +this.vehicle +"?referer=" +`${window?.location?.hostname || "localhost"}`,"_blank").focus();

					const redirectUrl =data.redirect +this.vehicle + "?referer=" + (window?.location?.hostname || "localhost");
					// Open tab first
					const newTab = window.open('', '_blank');
					if (newTab) {
						newTab.location.href = redirectUrl;
						newTab.focus();
					} else {
						window.location.href=redirectUrl;
						// Optional fallback
						// alert("Popup blocked. Please allow popups for this site.");
					}
				} else {
					console.log(
						"There was an error: " + (data.message || "Please try again later.")
					);
				}
			})
			.catch((err) => {
				console.log(err);
			});
	},
};

// Watch for DOM changes that might indicate page update
  const observer = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
      for (const node of mutation.addedNodes) {
        if (
          node.nodeType === 1 && // ELEMENT_NODE
          (node.id === 'auto-open-dia-widget' || node.querySelector?.('#auto-open-dia-widget'))
        ) {
          window.DIAWidget._domInt();
          return;
        }
      }
    }
  });

  observer.observe(document.body, {
    childList: true,
    subtree: true
  });