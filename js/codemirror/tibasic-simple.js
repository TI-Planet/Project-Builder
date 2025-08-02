// By Adriweb, 2023-2024
CodeMirror.defineSimpleMode("tibasic", {
    start: [
        {
            regex: /\b(Menu)(\()(Str\d|Ans|".*?"),(?:(?:Str\d|Ans|".*?"),[A-Z0-9θ]{1,2},?)+(?::|(\))|$)/,
            token: ["variable-3-bold", null, "menu"]
        },
        {
            regex: /".*?(?:"|→|$)/,
            token: "string"
        },
        {
            regex: /#.*/,
            token: "comment"
        },
        {
            regex: /→/,
            token: "store"
        },
        {
            regex: /(Lbl)( )([A-Z0-9θ]{1,2})(:|$)/,
            token: ["variable-3", null, "label", null]
        },
        {
            regex: /(Goto)( )([A-Z0-9θ]{1,2})(:|$)/,
            token: ["variable-3", null, "goto", null]
        },
        {
            regex: /(prgm)([A-Zθ][A-Z0-9θ]{0,7})(:|$)/,
            token: [null, "prgmname", null]
        },
        {
            regex: /(𝑖|π|RED|BLUE|BLACK|MAGENTA|GREEN|ORANGE|BROWN|NAVY|YELLOW|WHITE|LTBLUE|MEDGRAY|GRAY|LTGRAY|DARKGRAY)/,
            token: "atom"
        },
        {
            regex: /(If |Then|Else|While |Repeat |For\(|End|Return|Pause |Wait |Stop|IS>\(|DS<\(|Input |Prompt |Disp |Output\(|getkey|ClrHome|ClrTable|OpenLib\(|ExecLib |DispGraph)/,
            token: "keyword"
        },
        {
            regex: /(\*row\(|\*row\+\(|√\(|►Eff\(|►Nom\(|1-PropZInt\(|1-PropZTest\(|1-Var Stats |₁₀\^\(|2-PropZInt\(|2-PropZTest\(|2-Samp𝐅Test |2-SampTInt |2-SampTTest |2-SampZInt\(|2-SampZTest\(|2-Var Stats |³√\(|abs\(|angle\(|ANOVA\(|Archive |Asm\(|AsmComp\(|augment\(|bal\(|binomcdf\(|binompdf\(|BorderColor |checkTmr\(|Circle\(|ClrList |ClrTable|conj\(|cos\(|cos⁻¹\(|cosh\(|CubicReg |cumSum\(|dayOfWk\(|dbd\(|DelVar |det\(|dim\(|DrawF |DrawInv |e\^\(|Equ►String\(|eval\(|expr\(|ExpReg |𝐅cdf\(|Fill\(|Fix |fMax\(|fMin\(|fnInt\(|FnOff |FnOn |fPart\(|𝐅pdf\(|gcd\(|geometcdf\(|geometpdf\(|Get\(|GetCalc\(|getDate|getDtFmt|getDtStr\(|getKey|getTime|getTmFmt|getTmStr\(|GraphColor\(|GraphStyle\(|Horizontal |identity\(|imag\(|inString\(|int\(|invBinom\(|invNorm\(|invT\(|iPart\(|irr\(|isClockOn|lcm\(|length\(|Line\(|LinReg\(a\+bx\) |LinReg\(ax\+b\) |LinRegTInt |LinRegTTest |List►matr\(|ln\(|LnReg |log\(|logBASE\(|Logistic |Manual-Fit |Matr►list\(|max\(|mean\(|Med-Med |median\(|min\(|nDeriv\(|normalcdf\(|normalpdf\(|not\(|npv\(|OpenLib\(|P►Rx\(|P►Ry\(|piecewise\(|Plot1\(|Plot2\(|Plot3\(|poissoncdf\(|poissonpdf\(|prod\(|Pt-Change\(|Pt-Off\(|Pt-On\(|PwrReg |Pxl-Change\(|Pxl-Off\(|Pxl-On\(|pxl-Test\(|QuadReg |QuartReg |R►Pr\(|R►Pθ\(|rand|randBin\(|randInt\(|randIntNoRep\(|randM\(|randNorm\(|real\(|RecallGDB |RecallPic |ref\(|remainder\(|round\(|row\+\(|rowSwap\(|rref\(|Select\(|Send\(|seq\(|setDate\(|setDtFmt\(|setTime\(|setTmFmt\(|SetUpEditor |Shade_t\(|Shade\(|Shade𝐅\(|ShadeNorm\(|Shadeχ²\(|sin\(|sin⁻¹\(|sinh\(|sinh⁻¹\(|SinReg |cosh⁻¹\(|solve\(|SortA\(|SortD\(|startTmr|stdDev\(|StoreGDB |StorePic |String►Equ\(|sub\(|sum\(|T-Test |tan\(|tan⁻¹\(|Tangent\(|tanh\(|tanh⁻¹\(|tcdf\(|Text\(|TextColor\(|timeCnv\(|TInterval |toString\(|tpdf\(|UnArchive |variance\(|Vertical |Wait |Z-Test\(|ZInterval |ΔList\(|Σ\(|ΣInt\(|ΣPrn\(|χ²-Test\(|χ²GOF-Test\(|χ²pdf\()/,
            token: "basiccmd"
        },
        {
            regex: /(a\+b𝑖|AUTO|AxesOff|AxesOn |BackgroundOff|BackgroundOn |CENTER|CLASSIC|Clear Entries|ClockOff|ClockOn|ClrAllLists|ClrDraw|Connected|CoordOff|CoordOn|Copy Line|Cut Line|DARKGRAY|DEC|Degree|DependAsk|DependAuto|DetectAsymOff|DetectAsymOn|DiagnosticOff|DiagnosticOn|DispTable|Dot|Dot-Thick|Dot-Thin|Eng|ExecLib|Execute Program|ExprOff|ExprOn|Float|FRAC|FRAC-APPROX|Full|Func|G-T|GarbageCollect|GRAY|GREEN|GridDot |GridLine |GridOff|GridOn|Horiz|IndpntAsk|IndpntAuto|Insert Comment Above|Insert Line Above|LabelOff|LabelOn|LEFT|MATHPRINT|n⁄d|Normal|Param|Paste Line Below|PlotsOff |PlotsOn |Pmt_Bgn|Pmt_End|Polar|PolarGC|PrintScreen|Quartiles Setting…|QuickPlot&Fit-EQ|Quit Editor|Radian|re\^θ𝑖|Real|RectGC|RED|RIGHT|Sci|Seq|SEQ\(𝒏\)|SEQ\(𝒏\+1\)|SEQ\(𝒏\+2\)|Sequential|Simul|STATWIZARD OFF|STATWIZARD ON|Thick|Thin|Time|Trace|Un⁄d|Undo Clear|uvAxes|uwAxes|vwAxes|Web|ZBox|ZDecimal|ZFrac1⁄10|ZFrac1⁄2|ZFrac1⁄3|ZFrac1⁄4|ZFrac1⁄5|ZFrac1⁄8|ZInteger|Zoom In|Zoom Out|ZoomFit|ZoomRcl|ZoomStat|ZoomSto|ZPrevious|ZQuadrant1|ZSquare|ZStandard|ZTrig)/,
            token: "basicaction"
        },
        {
            regex: /Ans/,
            token: "ans"
        },
        {
            regex: /(ʟ[A-Zθ][0-9A-Zθ]{0,4})/,
            token: "basicvar-rw"
        },
        {
            regex: /(GDB[0-9]|(?:Image|Pic)[0-9]|[XY][₁₂₃₄₅₆]ᴛ|r[₁₂₃₄₅₆]|Str[0-9]|L[₁₂₃₄₅₆]|\[[A-J]\]|Y[₁₂₃₄₅₆₇₈₉₀]|∆Tbl|∆[XY]|FV|TMP|PV|𝗡|𝒏Max|𝒏Min|TblInput|TblStart|Tmax|Tmin|TraceStep|Tstep|YFact|Ymax|Ymin|Yscl|Z𝒏Max|Z𝒏Min|ZPlotStart|ZPlotStep|ZTmax|ZTmin|ZTstep|Zu\(𝒏Min\)|Zv\(𝒏Min\)|Zw\(𝒏Min\)|ZXmax|ZXmin|ZXres|ZXscl|ZYmax|ZYmin|ZYscl|Zθmax|Zθmin|Zθstep|θMax|θMin|θstep|XFact|Xmax|Xmin|Xres|Xscl)/,
            token: "basicvar-rw"
        },
        {
            regex: /(n[₁₂]?|p̂[₁₂]?|Q[₁₃]|[rR]²|𝒏|RegEQ|Sx[₁₂]|Sxp|tvm_FV|tvm_I%|tvm_𝗡|tvm_Pmt|tvm_PV|u\(𝒏-[12]\)|u\(𝒏\)|u\(𝒏\+1\)|lower|upper|v\(𝒏-[12]\)|v\(𝒏\)|v\(𝒏\+1\)|w\(𝒏-[12]\)|w\(𝒏\)|w\(𝒏\+1\)|[xy][₁₂₃]|x̄[₁₂₃]|x̄|ȳ|Σ[xy]²?|σ[xy]|Σxy|χ²)/,
            token: "basicvar-ro"
        },
        {
            regex: /([A-Zθ])/,
            token: "basicvar-rw"
        },
        {
            regex: /(\d*\.?\d+?)/,
            token: "number"
        },
        {
            regex: /([-+\/*=≠<>≤≥!]| and | or | xor |not\(| n[CP]r |ᴇ|\^|ˣ√)/,
            token: "operator"
        },
        {
            regex: /(►DMS|►Dec|►Frac|ʳ|°|⁻¹|²|ᵀ|³|!|%|►n⁄d◄►Un⁄d|►F◄►D)/,
            token: "operator-2"
        },
        {
            regex: /(Then|Else)/,
            indent: true
        },
        {
            regex: /(End)/,
            dedent: true
        }
    ],
    meta: {
        dontIndentStates: ["comment"],
        lineComment: "#"
    }
});

CodeMirror.defineMIME("text/x-tibasic", "tibasic");
CodeMirror.defineMIME("text/x-tibasicfr", { name: "tibasic", locale: "fr" });
