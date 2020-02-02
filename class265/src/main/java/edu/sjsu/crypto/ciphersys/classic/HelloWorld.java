package edu.sjsu.crypto.ciphersys.classic;

import edu.sjsu.yazdankhah.crypto.util.cipherutils.ConversionUtil;
import edu.sjsu.yazdankhah.crypto.util.cipherutils.PrintUtil;
import lombok.extern.slf4j.Slf4j;

/**
 * This is a Hello Word class for Cipher Systems project. 
 *  
 * @author ahmad
 */
@Slf4j
public class HelloWorld {
  
  public static void greeting() {
    log.info("Hello World!");
  }
  
  public static void usingCryptoUtil1() {
	  String text = "attack";
	  String binStr = ConversionUtil.textToBinStr(text);
	  
	  PrintUtil.printStrFormatted(binStr, "Binary string 1");
  }
  
  public static void usingCryptoUtil2() {
	  String text = "attack";
	  String binStr = ConversionUtil.textToBinStr(text);
	  
	  PrintUtil.putStrInBox(binStr, "This is the binary string in box");
  }
  
}
